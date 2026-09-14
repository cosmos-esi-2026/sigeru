<?php
// ============================================================================
//  SiGeRU 2026 - Validaciones de formato (Ciberseguridad, 1ra/2da entrega)
//
//  Antes de esto, el backend solo comprobaba que nombre y email no vinieran
//  vacios (empty()): un email sin arroba o una contrasena de un caracter
//  pasaban igual, porque la unica validacion real vivia en el HTML del
//  frontend (type="email", minlength en el navegador), que cualquiera puede
//  saltear mandando la peticion directo a la API con curl. Esta clase es la
//  validacion que de verdad no se puede evitar.
// ============================================================================

class Validacion
{
    /**
     * Politica de contrasenas: largo minimo 8. Se eligio esto y no una regla
     * de complejidad forzada (mayuscula + numero + simbolo) porque el NIST
     * SP 800-63B recomienda priorizar el largo por sobre la complejidad: las
     * reglas de composicion, en la practica, llevan a patrones predecibles
     * ("Clave123!") que no son mas dificiles de adivinar. No se aplica dentro
     * de login(): una cuenta con una contrasena vieja mas corta que la
     * politica actual tiene que poder seguir entrando.
     */
    public const CLAVE_MINIMA = 8;

    public static function email(string $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function contrasena(string $valor): bool
    {
        return strlen($valor) >= self::CLAVE_MINIMA;
    }
}
