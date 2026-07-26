<?php

class Contenedor
{
    private static array $contenedores = [
        [
            "id_contenedor" => 1, "codigo" => "CNT-001",
            "direccion" => "Av. Italia esq. Propios", "capacidad" => 1100,
            "estado" => "disponible", "tipo_residuo" => "Organico"
        ],
        [
            "id_contenedor" => 2, "codigo" => "CNT-002",
            "direccion" => "Plaza Independencia", "capacidad" => 1100,
            "estado" => "lleno", "tipo_residuo" => "Plastico"
        ],
        [
            "id_contenedor" => 3, "codigo" => "CNT-003",
            "direccion" => "Bvar. Artigas 2200", "capacidad" => 800,
            "estado" => "disponible", "tipo_residuo" => "Vidrio"
        ],
    ];

    public static function listar(): array
    {
        return self::$contenedores;
    }

    public static function obtener(int $id): ?array
    {
        foreach (self::$contenedores as $c) {
            if ($c["id_contenedor"] === $id) {
                return $c;
            }
        }
        return null;
    }

    public static function crear(array $datos): array
    {
        $nuevoId = count(self::$contenedores) + 1;
        return [
            "id_contenedor" => $nuevoId,
            "codigo"        => $datos["codigo"]       ?? "",
            "direccion"     => $datos["direccion"]    ?? "",
            "capacidad"     => $datos["capacidad"]    ?? null,
            "estado"        => $datos["estado"]       ?? "disponible",
            "tipo_residuo"  => $datos["tipo_residuo"] ?? null,
        ];
    }
}