<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Presentation;

use Closure;
use MicroHis\TrasladosAltas\Application\Autorizador;
use MicroHis\TrasladosAltas\Application\RegistroInternacion;

/**
 * Controller de consola: traduce argumentos posicionales a campos con nombre y delega en EjecutorOperaciones.
 * Sin SQL ni reglas; las dependencias se piden solo si la solicitud es válida.
 */
final class ConsolaTraslados
{
    private readonly EjecutorOperaciones $ejecutor;

    /** @param Closure(): array{0: RegistroInternacion, 1: Autorizador} $dependencias */
    public function __construct(Closure $dependencias, private readonly string $actorRefId)
    {
        $this->ejecutor = new EjecutorOperaciones($dependencias);
    }

    /**
     * @param list<string> $argumentos trasladar <estancia> <cama> <motivo> | alta <estancia>
     * @return array{codigo: int, cuerpo: array<string, mixed>}
     */
    public function atender(array $argumentos): array
    {
        [$tipo, $campos] = match ($argumentos[0] ?? '') {
            'trasladar' => [EjecutorOperaciones::TRASLADO, ['estancia_esperada_id' => $argumentos[1] ?? '', 'cama_destino_id' => $argumentos[2] ?? '',
                'motivo' => $argumentos[3] ?? '']],
            'alta' => [EjecutorOperaciones::ALTA, ['estancia_esperada_id' => $argumentos[1] ?? '']],
            default => ['', []],
        };

        $respuesta = $this->ejecutor->ejecutar($tipo, $campos, $this->actorRefId);
        // La ayuda de los argumentos solo tiene sentido aquí: el API no debe exponer sintaxis de consola.
        if ($respuesta['codigo'] === VistaResultado::SOLICITUD_INVALIDA) {
            $respuesta['cuerpo']['uso'] = 'trasladar <estancia_id> <cama_destino_id> <motivo> | alta <estancia_id>';
        }

        return $respuesta;
    }
}
