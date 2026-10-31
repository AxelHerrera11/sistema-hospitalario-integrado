<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Domain\SolicitudAlta;
use MicroHis\TrasladosAltas\Domain\SolicitudInvalida;
use MicroHis\TrasladosAltas\Domain\SolicitudTraslado;
use MicroHis\TrasladosAltas\Presentation\EjecutorOperaciones;
use MicroHis\TrasladosAltas\Presentation\VistaResultado;

// Contrato de entrada y salida del componente común de los controllers.
$campos = ['estancia_esperada_id' => ESTANCIA, 'cama_destino_id' => CAMA_B, 'motivo' => 'Aislamiento'];
check('campos de traslado → SolicitudTraslado', EjecutorOperaciones::solicitud(EjecutorOperaciones::TRASLADO, $campos) instanceof SolicitudTraslado);
check('campos de alta → SolicitudAlta (ignora destino y motivo)', EjecutorOperaciones::solicitud(EjecutorOperaciones::ALTA, $campos) instanceof SolicitudAlta);
check('tipo desconocido → SolicitudInvalida(operacion)', thrown(fn () => EjecutorOperaciones::solicitud('mover', $campos), SolicitudInvalida::class)?->campo === 'operacion');
// Regresión: antes, un motivo enviado como arreglo JSON se convertía en "Array" y el traslado se aceptaba.
check('motivo no escalar → SolicitudInvalida(motivo), no "Array"', thrown(fn () => EjecutorOperaciones::solicitud(EjecutorOperaciones::TRASLADO,
    ['motivo' => ['x']] + $campos), SolicitudInvalida::class)?->campo === 'motivo');

$pedidas = 0;
$ejecutor = new EjecutorOperaciones(static function () use (&$pedidas): array {
    $pedidas++;

    return [hospitalFalso(), new AutorizadorFijo(true)];
});
check('solicitud inválida → 64 sin pedir dependencias', $ejecutor->ejecutar(EjecutorOperaciones::ALTA, [], ACTOR)['codigo'] === VistaResultado::SOLICITUD_INVALIDA && $pedidas === 0);
check('solicitud válida → la operación se ejecuta con una sola petición de dependencias',
    $ejecutor->ejecutar(EjecutorOperaciones::TRASLADO, $campos, ACTOR)['codigo'] === VistaResultado::EXITO && $pedidas === 1);
check('HTTP con motivo como arreglo → 422', http(hospitalFalso(), 'POST', '/traslados', cuerpo: ['motivo' => ['x']] + $campos)['estado'] === 422);
