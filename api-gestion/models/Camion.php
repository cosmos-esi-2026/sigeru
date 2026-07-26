<?php

class Camion
{
    private static array $camiones = [
        [
            "id_camion" => 1, "matricula" => "SBA-1234",
            "marca" => "Volkswagen", "modelo" => "Delivery", "capacidad" => 4000,
            "estado" => "operativo"
        ],
        [
            "id_camion" => 2, "matricula" => "SBB-5678",
            "marca" => "Iveco", "modelo" => "Tector", "capacidad" => 6000,
            "estado" => "en_ruta"
        ],
        [
            "id_camion" => 3, "matricula" => "SBC-9012",
            "marca" => "Mercedes-Benz", "modelo" => "Accelo", "capacidad" => 5000,
            "estado" => "mantenimiento"
        ],
    ];

    public static function listar(): array
    {
        return self::$camiones;
    }

    public static function obtener(int $id): ?array
    {
        foreach (self::$camiones as $c) {
            if ($c["id_camion"] === $id) {
                return $c;
            }
        }
        return null;
    }

    public static function crear(array $datos): array
    {
        $nuevoId = count(self::$camiones) + 1;
        return [
            "id_camion" => $nuevoId,
            "matricula" => $datos["matricula"] ?? "",
            "marca"     => $datos["marca"]     ?? null,
            "modelo"    => $datos["modelo"]    ?? null,
            "capacidad" => $datos["capacidad"] ?? null,
            "estado"    => $datos["estado"]    ?? "operativo",
        ];
    }
}
