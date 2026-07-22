<?php
class Usuario
{
    private static array $usuarios = [
        [
            "id_usuario" => 1, "nombre" => "Ana", "apellido" => "Perez",
            "email" => "admin@sigeru.uy", "rol" => "Administrador", "estado" => "activo"
        ],
        [
            "id_usuario" => 2, "nombre" => "Luis", "apellido" => "Gomez",
            "email" => "cuadrilla@sigeru.uy", "rol" => "Cuadrilla", "estado" => "activo"
        ],
        [
            "id_usuario" => 3, "nombre" => "Marta", "apellido" => "Rodriguez",
            "email" => "vecino@sigeru.uy", "rol" => "Vecino", "estado" => "activo"
        ],
    ];

    public static function listar(): array
    {
        return self::$usuarios;
    }

    public static function obtener(int $id): ?array
    {
        foreach (self::$usuarios as $u) {
            if ($u["id_usuario"] === $id) {
                return $u;
            }
        }
        return null;
    }

    public static function crear(array $datos): array
    {
        $nuevoId = count(self::$usuarios) + 1;
        return [
            "id_usuario" => $nuevoId,
            "nombre"     => $datos["nombre"]   ?? "",
            "apellido"   => $datos["apellido"] ?? "",
            "email"      => $datos["email"]    ?? "",
            "rol"        => $datos["rol"]      ?? "Vecino",
            "estado"     => "activo",
        ];
    }

    public static function login(string $email, string $clave): ?array
    {
        foreach (self::$usuarios as $u) {
            if ($u["email"] === $email && $clave === "123456") {
                return $u;
            }
        }
        return null;
    }
}
