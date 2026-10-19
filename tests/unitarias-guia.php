<?php

// Guia "donde tiro esto" (extras, parte 19): los datos son estaticos (landing/data/residuos.json) y la
// busqueda corre en el navegador; aca se comprueba que los datos esten completos, sean coherentes y citen la fuente.

titulo("38. Guia de clasificacion: datos y fuente");

$ruta = __DIR__ . "/../landing/data/residuos.json";
$guia = json_decode((string) file_get_contents($ruta), true);
afirmar("residuos.json es JSON valido", true, is_array($guia));

$items = $guia["items"] ?? [];
afirmar("La guia tiene al menos 55 residuos", true, count($items) >= 55);

$tiposValidos = ["Organico", "Plastico", "Vidrio", "Papel", "Metal", "Mixto", "Especial"];
$idsFuente = array_column($guia["fuentes"] ?? [], "id");
$normalizar = fn(string $t) => trim(preg_replace("/\s+/", " ", preg_replace("/[^a-z0-9 ]+/", " ", strtolower(strtr(
    $t, ["á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u", "ü" => "u", "ñ" => "n", "Á" => "a", "É" => "e", "Í" => "i", "Ó" => "o", "Ú" => "u"])))));

$problemas = [];
$vistos = [];
foreach ($items as $i => $it) {
    $n = $it["nombre"] ?? "#$i";
    foreach (["nombre", "tipo", "contenedor", "consejo", "fuente", "pagina"] as $campo) {
        if (!array_key_exists($campo, $it) || $it[$campo] === "" || $it[$campo] === null) $problemas[] = "$n: falta $campo";
    }
    if (!in_array($it["tipo"] ?? "", $tiposValidos, true)) $problemas[] = "$n: tipo '" . ($it["tipo"] ?? "") . "'";
    if (!in_array($it["fuente"] ?? "", $idsFuente, true)) $problemas[] = "$n: fuente inexistente";
    if (!is_int($it["pagina"] ?? null) || $it["pagina"] < 1) $problemas[] = "$n: pagina invalida";
    if (!is_array($it["sinonimos"] ?? null)) $problemas[] = "$n: sinonimos no es lista";
    $clave = $normalizar($n);
    if (isset($vistos[$clave])) $problemas[] = "$n: nombre repetido";
    $vistos[$clave] = true;
}
afirmar("Cada residuo tiene nombre, tipo valido, contenedor, consejo, fuente existente y pagina", [], $problemas);

$sinTipoDelSistema = array_values(array_unique(array_column($items, "tipo")));
afirmar("Estan los siete tipos (los seis del sistema mas «Especial»)", $tiposValidos, array_values(array_intersect($tiposValidos, $sinTipoDelSistema)));

$urls = array_merge(array_column($guia["fuentes"] ?? [], "url"), array_column($guia["enlaces_del_manual"] ?? [], "url"));
afirmar("Fuentes y enlaces del manual son https", true, $urls !== [] && count(array_filter($urls, fn($u) => str_starts_with($u, "https://"))) === count($urls));
afirmar("El aviso dice que las reglas cambian y varian por departamento", true, str_contains($guia["aviso"] ?? "", "varían") && str_contains($guia["aviso"] ?? "", "cambiar"));

// Los ejemplos que se muestran en la pagina: buscar en el mismo orden que el navegador (todas las palabras).
$buscar = function (string $q) use ($items, $normalizar) {
    $palabras = array_filter(explode(" ", $normalizar($q)));
    return array_values(array_filter($items, function ($it) use ($palabras, $normalizar) {
        $pajar = $normalizar($it["nombre"] . " " . implode(" ", $it["sinonimos"]) . " " . $it["tipo"]);
        foreach ($palabras as $p) if (!str_contains($pajar, $p)) return false;
        return true;
    }));
};
afirmar("«botella» devuelve plastico y vidrio", ["Plastico", "Vidrio"], array_values(array_intersect(["Plastico", "Vidrio"], array_column($buscar("botella"), "tipo"))));
afirmar("«pila» devuelve el tipo Especial", true, in_array("Especial", array_column($buscar("pila"), "tipo"), true));
afirmar("«PIZZA» (mayusculas) encuentra la caja con grasa en Mixto", true, in_array("Mixto", array_column($buscar("PIZZA"), "tipo"), true));
afirmar("Sin tildes: «bateria» encuentra las baterias", true, count($buscar("bateria")) >= 1);
afirmar("Algo que no existe no devuelve nada", [], $buscar("xyzzy"));

$html = (string) file_get_contents(__DIR__ . "/../landing/donde-tiro.html");
afirmar("La pagina tiene el buscador con etiqueta, el aviso en vivo y la fuente a la vista", [true, true, true], [
    str_contains($html, 'for="q"'), str_contains($html, 'aria-live="polite"'), str_contains($html, 'id="fuentes-guia"'),
]);
afirmar("La landing y «Informar un problema» enlazan la guia", [true, true], [
    str_contains((string) file_get_contents(__DIR__ . "/../landing/index.html"), "donde-tiro.html"),
    str_contains((string) file_get_contents(__DIR__ . "/../frontend-usuarios/informar-problema.html"), "donde-tiro.html"),
]);
