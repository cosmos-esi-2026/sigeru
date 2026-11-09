<?php

// Trazado de una ruta por las calles (plan de rutas para la defensa, bloque 3; 22/10/2026).
//
// Pide el camino a servicios publicos de rutas basados en OpenStreetMap, en cascada: Valhalla con perfil de camion -> OSRM de FOSSGIS -> OSRM demo.
// Si ninguno responde bien, la ruta cae a LINEA RECTA entre sus paradas y la pantalla avisa. OpenRouteService no se usa: pide cuenta y clave.
// Se cumplen las condiciones de uso de los servidores de FOSSGIS e.V. (https://www.fossgis.de/arbeitsgruppen/osm-server/nutzungsbedingungen/,
// leidas el 22/10/2026): User-Agent propio que identifica la aplicacion (sin datos personales), una sola conexion, como maximo UN pedido por
// segundo, las URL se pueden cambiar por variables de entorno (no van fijas) y las pantallas que dibujan la ruta citan a OpenStreetMap.
//
// Todo lo que no es el pedido HTTP es PURO y se prueba sin red (tests/unitarias-rutas.php). El pedido real se puede reemplazar al llamar a calcular().
// DESDE EL 30/10/2026 (congelamiento) el modo por defecto es RECTA: guardar una ruta NO pide nada a internet (antes podia tardar hasta 30 s si no habia
// conexion). Las 40 rutas del seed conservan el trazado por calles que ya tienen guardado. Para volver a pedir las calles hay que arrancar Apache con
// SIGERU_TRAZADO_MODO=calles (el codigo de los servicios sigue aca, dormido, con sus pruebas).
final class Trazado
{
    public const TIPO_CALLES = "calles";
    public const TIPO_RECTA = "recta";

    public const USER_AGENT = "SiGeRU-ESI-2026/1.0 (proyecto estudiantil; https://github.com/cosmos-esi-2026/sigeru)";
    public const REFERER = "https://github.com/cosmos-esi-2026/sigeru";
    public const ESPERA_ENTRE_PEDIDOS_MS = 1200;      // la politica dice un pedido por segundo como maximo
    public const TIMEOUT_PEDIDO_S = 8;
    public const PRESUPUESTO_S = 30;                  // tiempo total maximo antes de rendirse y dibujar en linea recta
    public const MAX_PUNTOS_VALHALLA = 10;            // el servidor publico de Valhalla no admite mas de 10 puntos por pedido
    public const DISTANCIA_MAX_PARADA_M = 250.0;      // si una parada queda mas lejos que esto de la linea, la respuesta se descarta

    // Dimensiones SUPUESTAS de un camion recolector rigido (decision del equipo, no medidas): el perfil por omision de Valhalla es el de un
    // camion articulado de 21 m, que descartaria calles por las que un recolector si pasa.
    public const VEHICULO = ["length" => 9.0, "width" => 2.5, "height" => 3.5, "weight" => 16.0, "axle_load" => 8.0];

    // --- Configuracion --------------------------------------------------------------------------------------

    public static function proveedores(): array
    {
        $env = static function (string $clave, string $porDefecto): string {
            $valor = getenv($clave);
            return $valor === false || $valor === "" ? $porDefecto : $valor;
        };
        return [
            ["nombre" => "valhalla-truck", "tipo" => "valhalla", "url" => $env("SIGERU_VALHALLA_URL", "https://valhalla1.openstreetmap.de/route")],
            ["nombre" => "osrm-fossgis-auto", "tipo" => "osrm", "url" => $env("SIGERU_OSRM_URL", "https://routing.openstreetmap.de/routed-car/route/v1/driving/")],
            ["nombre" => "osrm-demo-auto", "tipo" => "osrm", "url" => $env("SIGERU_OSRM_RESPALDO_URL", "https://router.project-osrm.org/route/v1/driving/")],
        ];
    }

    public static function modo(): string
    {
        return getenv("SIGERU_TRAZADO_MODO") === "calles" ? self::TIPO_CALLES : self::TIPO_RECTA;
    }

    // --- Piezas puras ---------------------------------------------------------------------------------------

    // Huella de la lista de paradas (en orden): si cambia una parada o su ubicacion, el trazado guardado deja de valer.
    // Es la misma cuenta que hace generadores/importar_contenedores_intendencia.py: sha256 de "lat,lon;lat,lon;..." con 7 decimales.
    public static function firma(array $paradas): string
    {
        return hash("sha256", implode(";", array_map(static fn(array $p) => sprintf("%.7f,%.7f", (float) $p["latitud"], (float) $p["longitud"]), $paradas)));
    }

    // [[lon, lat], ...] de las paradas, en orden.
    public static function puntos(array $paradas): array
    {
        return array_map(static fn(array $p) => [round((float) $p["longitud"], 6), round((float) $p["latitud"], 6)], $paradas);
    }

    public static function geojson(array $coordenadas): array
    {
        return ["type" => "LineString", "coordinates" => $coordenadas];
    }

    public static function distanciaM(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $f1 = deg2rad($lat1);
        $f2 = deg2rad($lat2);
        $a = sin(($f2 - $f1) / 2) ** 2 + cos($f1) * cos($f2) * sin(deg2rad($lon2 - $lon1) / 2) ** 2;
        return 2 * 6371008.8 * asin(sqrt($a));
    }

    public static function largoRectaM(array $coordenadas): float
    {
        $suma = 0.0;
        for ($i = 1; $i < count($coordenadas); $i++) {
            $suma += self::distanciaM($coordenadas[$i - 1][1], $coordenadas[$i - 1][0], $coordenadas[$i][1], $coordenadas[$i][0]);
        }
        return $suma;
    }

    // Trazado de emergencia: una linea recta entre las paradas, en orden.
    public static function recta(array $paradas): array
    {
        $coordenadas = self::puntos($paradas);
        return ["tipo" => self::TIPO_RECTA, "servicio" => null, "coordenadas" => $coordenadas,
                "distancia_m" => (int) round(self::largoRectaM($coordenadas)), "duracion_s" => null];
    }

    // Polilinea codificada de Valhalla (precision 6) -> [[lon, lat], ...]
    public static function decodificarPolilinea(string $texto, int $precision = 6): array
    {
        $indice = 0;
        $largo = strlen($texto);
        $lat = 0;
        $lon = 0;
        $factor = 10 ** $precision;
        $puntos = [];
        while ($indice < $largo) {
            foreach ([true, false] as $esLat) {
                $desplazamiento = 0;
                $resultado = 0;
                do {
                    if ($indice >= $largo) {
                        throw new RuntimeException("polilinea truncada");
                    }
                    $b = ord($texto[$indice++]) - 63;
                    $resultado |= ($b & 0x1F) << $desplazamiento;
                    $desplazamiento += 5;
                } while ($b >= 0x20);
                $delta = ($resultado & 1) ? ~($resultado >> 1) : ($resultado >> 1);
                if ($esLat) {
                    $lat += $delta;
                } else {
                    $lon += $delta;
                }
            }
            $puntos[] = [round($lon / $factor, 6), round($lat / $factor, 6)];
        }
        return $puntos;
    }

    // Parte la lista en tramos de a lo sumo $max puntos que se solapan en un punto (el ultimo de uno es el primero del siguiente).
    public static function tramos(array $paradas, int $max): array
    {
        $tramos = [];
        $desde = 0;
        while ($desde < count($paradas) - 1) {
            $tramo = array_slice($paradas, $desde, $max);
            $tramos[] = $tramo;
            $desde += count($tramo) - 1;
        }
        return $tramos;
    }

    public static function cuerpoValhalla(array $paradas): string
    {
        return json_encode([
            "locations"          => array_map(static fn(array $p) => ["lat" => (float) $p["latitud"], "lon" => (float) $p["longitud"], "type" => "break"], $paradas),
            "costing"            => "truck",
            "costing_options"    => ["truck" => self::VEHICULO],
            "directions_options" => ["units" => "kilometers"],
            "shape_format"       => "polyline6",
        ], JSON_UNESCAPED_SLASHES);
    }

    // De la respuesta de Valhalla: [coordenadas, metros, segundos]
    public static function leerValhalla(array $datos): array
    {
        if (!isset($datos["trip"]["legs"], $datos["trip"]["summary"]["length"], $datos["trip"]["summary"]["time"])) {
            throw new RuntimeException("respuesta de Valhalla sin ruta");
        }
        $coordenadas = [];
        foreach ($datos["trip"]["legs"] as $tramo) {
            $pts = self::decodificarPolilinea((string) ($tramo["shape"] ?? ""), 6);
            array_push($coordenadas, ...($coordenadas === [] ? $pts : array_slice($pts, 1)));
        }
        return [$coordenadas, (float) $datos["trip"]["summary"]["length"] * 1000.0, (float) $datos["trip"]["summary"]["time"]];
    }

    public static function urlOsrm(string $base, array $paradas): string
    {
        return $base . implode(";", array_map(static fn(array $p) => sprintf("%.6f,%.6f", (float) $p["longitud"], (float) $p["latitud"]), $paradas))
            . "?overview=full&geometries=geojson&steps=false";
    }

    // De la respuesta de OSRM: [coordenadas, metros, segundos]
    public static function leerOsrm(array $datos): array
    {
        if (($datos["code"] ?? "") !== "Ok" || !isset($datos["routes"][0]["geometry"]["coordinates"])) {
            throw new RuntimeException("respuesta de OSRM: " . ($datos["code"] ?? "sin codigo"));
        }
        $coordenadas = array_map(static fn(array $c) => [round((float) $c[0], 6), round((float) $c[1], 6)], $datos["routes"][0]["geometry"]["coordinates"]);
        return [$coordenadas, (float) $datos["routes"][0]["distance"], (float) $datos["routes"][0]["duration"]];
    }

    // Distancia de un punto a un segmento, en metros, sobre un plano local (alcanza para decenas de metros).
    private static function metrosAlSegmento(array $p, array $a, array $b, float $lat0): float
    {
        $kx = 111320.0 * cos(deg2rad($lat0));
        $ky = 110574.0;
        [$px, $py] = [$p[0] * $kx, $p[1] * $ky];
        [$ax, $ay] = [$a[0] * $kx, $a[1] * $ky];
        [$bx, $by] = [$b[0] * $kx, $b[1] * $ky];
        $dx = $bx - $ax;
        $dy = $by - $ay;
        $t = ($dx == 0 && $dy == 0) ? 0.0 : max(0.0, min(1.0, (($px - $ax) * $dx + ($py - $ay) * $dy) / ($dx * $dx + $dy * $dy)));
        return hypot($px - ($ax + $t * $dx), $py - ($ay + $t * $dy));
    }

    // Lo mas lejos que queda una parada de la linea, en metros.
    public static function paradaMasLejosM(array $paradas, array $coordenadas): float
    {
        if (count($coordenadas) < 2) {
            return INF;
        }
        $peor = 0.0;
        foreach (self::puntos($paradas) as $p) {
            $minimo = INF;
            for ($i = 0; $i < count($coordenadas) - 1; $i++) {
                $minimo = min($minimo, self::metrosAlSegmento($p, $coordenadas[$i], $coordenadas[$i + 1], $p[1]));
            }
            $peor = max($peor, $minimo);
        }
        return $peor;
    }

    // Una respuesta buena es una linea que pasa cerca de todas las paradas. Devuelve null si esta bien, o el motivo.
    public static function errorDeCalidad(array $paradas, array $coordenadas): ?string
    {
        if (count($coordenadas) < 2) {
            return "la linea no tiene puntos";
        }
        $lejos = self::paradaMasLejosM($paradas, $coordenadas);
        return $lejos > self::DISTANCIA_MAX_PARADA_M ? sprintf("una parada queda a %.0f m de la linea", $lejos) : null;
    }

    // --- Pedidos --------------------------------------------------------------------------------------------

    // Pedido HTTP real: devuelve [codigo, cuerpo]. Nunca lanza por un fallo de red: devuelve codigo 0.
    public static function http(string $metodo, string $url, ?string $cuerpo = null): array
    {
        if (!function_exists("curl_init")) {
            return [0, ""];
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_REFERER        => self::REFERER,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => self::TIMEOUT_PEDIDO_S,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER     => $cuerpo === null ? [] : ["Content-Type: application/json"],
        ]);
        if ($cuerpo !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $cuerpo);
        }
        $respuesta = curl_exec($curl);
        $codigo = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        return [$codigo, is_string($respuesta) ? $respuesta : ""];
    }

    private static function json(array $respuesta, string $quien): array
    {
        [$codigo, $cuerpo] = $respuesta;
        if ($codigo !== 200) {
            throw new RuntimeException("$quien respondio " . ($codigo === 0 ? "sin conexion" : "HTTP $codigo"));
        }
        $datos = json_decode($cuerpo, true);
        if (!is_array($datos)) {
            throw new RuntimeException("$quien devolvio algo que no es JSON");
        }
        return $datos;
    }

    // $http: callable(string $metodo, string $url, ?string $cuerpo): array{int, string}. $dormir: callable(int $milisegundos).
    // Devuelve ["tipo", "servicio", "coordenadas", "distancia_m", "duracion_s", "intentos"] y, si cae a linea recta, tambien "aviso".
    public static function calcular(array $paradas, ?callable $http = null, ?callable $dormir = null): array
    {
        $http ??= [self::class, "http"];
        $dormir ??= static function (int $ms): void {
            usleep($ms * 1000);
        };
        $recta = self::recta($paradas);
        if (count($paradas) < 2) {
            return $recta + ["intentos" => [], "aviso" => "Hacen falta al menos 2 paradas para trazar la ruta."];
        }
        if (self::modo() === self::TIPO_RECTA) {
            return $recta + ["intentos" => [], "aviso" => "La ruta se dibuja en línea recta entre sus paradas."];
        }

        $inicio = microtime(true);
        $ultimo = null;
        $pedir = static function (string $metodo, string $url, ?string $cuerpo) use ($http, $dormir, &$ultimo): array {
            if ($ultimo !== null) {
                $falta = self::ESPERA_ENTRE_PEDIDOS_MS - (int) round((microtime(true) - $ultimo) * 1000);
                if ($falta > 0) {
                    $dormir($falta);
                }
            }
            $ultimo = microtime(true);
            return $http($metodo, $url, $cuerpo);
        };

        $intentos = [];
        foreach (self::proveedores() as $proveedor) {
            if (microtime(true) - $inicio > self::PRESUPUESTO_S) {
                $intentos[] = "se acabó el tiempo disponible";
                break;
            }
            try {
                $coordenadas = [];
                $metros = 0.0;
                $segundos = 0.0;
                if ($proveedor["tipo"] === "valhalla") {
                    foreach (self::tramos($paradas, self::MAX_PUNTOS_VALHALLA) as $tramo) {
                        [$pts, $m, $s] = self::leerValhalla(self::json($pedir("POST", $proveedor["url"], self::cuerpoValhalla($tramo)), $proveedor["nombre"]));
                        array_push($coordenadas, ...($coordenadas === [] ? $pts : array_slice($pts, 1)));
                        $metros += $m;
                        $segundos += $s;
                    }
                } else {
                    [$coordenadas, $metros, $segundos] = self::leerOsrm(self::json($pedir("GET", self::urlOsrm($proveedor["url"], $paradas), null), $proveedor["nombre"]));
                }
                $error = self::errorDeCalidad($paradas, $coordenadas);
                if ($error !== null) {
                    throw new RuntimeException($error);
                }
                return ["tipo" => self::TIPO_CALLES, "servicio" => $proveedor["nombre"], "coordenadas" => $coordenadas,
                        "distancia_m" => (int) round($metros), "duracion_s" => (int) round($segundos), "intentos" => $intentos];
            } catch (Throwable $e) {
                $intentos[] = $proveedor["nombre"] . ": " . $e->getMessage();
            }
        }
        return $recta + ["intentos" => $intentos, "aviso" => "No se pudo trazar por las calles (los servicios de rutas no respondieron): se dibuja en línea recta. Probá de nuevo más tarde."];
    }
}
