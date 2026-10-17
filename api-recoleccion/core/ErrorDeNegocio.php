<?php

class ErrorDeNegocio extends RuntimeException
{
    public function __construct(string $mensaje, private int $estadoHttp, private array $datos = [])
    {
        parent::__construct($mensaje);
    }

    public function estadoHttp(): int
    {
        return $this->estadoHttp;
    }

    public function datos(): array
    {
        return $this->datos;
    }
}
