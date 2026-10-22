<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Presentation;

use Closure;
use MicroHis\TrasladosAltas\Application\Autorizador;
use MicroHis\TrasladosAltas\Application\ConsultarInternacion;
use MicroHis\TrasladosAltas\Application\FalloPersistencia;
use MicroHis\TrasladosAltas\Application\NoAutorizado;
use MicroHis\TrasladosAltas\Application\RegistroInternacion;
use MicroHis\TrasladosAltas\Domain\Identificador;
use MicroHis\TrasladosAltas\Domain\SolicitudInvalida;

/**
 * PROTOTIPO de API HTTP del micro-HIS (Semana 5). Delega en EjecutorOperaciones y VistaResultado: solo traduce HTTP.
 * El actor llega en la cabecera X-Actor-Ref; en la integración la reemplazan JWT, tenant y RBAC de Laravel.
 */
final class HttpTraslados
{
    /** Estado HTTP por código de VistaResultado. El incierto es 500 sin Retry-After: hay que consultar antes de reintentar. */
    private const ESTADOS = [VistaResultado::EXITO => 200, VistaResultado::RECHAZO => 409, VistaResultado::FALLO => 503,
        VistaResultado::INCIERTO => 500, VistaResultado::SOLICITUD_INVALIDA => 422];

    /** @param Closure(): array{0: RegistroInternacion, 1: Autorizador} $dependencias */
    public function __construct(private readonly Closure $dependencias)
    {
    }

    /** Segundos sugeridos antes de reintentar un 503: el fallo es confirmado, así que reintentar es seguro. */
    public const REINTENTAR_EN = 5;

    /**
     * Solo el 503 lleva Retry-After. El 500 (incierto) no lo lleva: antes hay que consultar el estado.
     *
     * @return array{estado: int, cuerpo: array<string, mixed>, cabeceras: array<string, string>}
     */
    public function atender(string $metodo, string $ruta, ?string $actorRefId, string $cuerpo): array
    {
        return self::conCabeceras($this->responder($metodo, $ruta, $actorRefId, $cuerpo));
    }

    /**
     * @param array{estado: int, cuerpo: array<string, mixed>} $respuesta
     * @return array{estado: int, cuerpo: array<string, mixed>, cabeceras: array<string, string>}
     */
    public static function conCabeceras(array $respuesta): array
    {
        return $respuesta + ['cabeceras' => $respuesta['estado'] === 503 ? ['Retry-After' => (string) self::REINTENTAR_EN] : []];
    }

    /** @return array{estado: int, cuerpo: array<string, mixed>} */
    private function responder(string $metodo, string $ruta, ?string $actorRefId, string $cuerpo): array
    {
        $consulta = preg_match('#^/estancias/([^/]+)/internacion$#', $ruta, $m) === 1;
        if (!$consulta && !in_array($ruta, ['/traslados', '/altas'], true)) {
            return self::error(404, 'RUTA_INEXISTENTE');
        }
        if ($metodo !== ($consulta ? 'GET' : 'POST')) {
            return self::error(405, 'METODO_NO_PERMITIDO');
        }
        if ($actorRefId === null || trim($actorRefId) === '') {
            return self::error(401, 'ACTOR_REQUERIDO');
        }
        if ($consulta) {
            return $this->consultar($actorRefId, rawurldecode($m[1]));
        }
        $datos = json_decode($cuerpo, true);
        if (!is_array($datos)) {
            return self::error(400, 'JSON_INVALIDO');
        }
        $tipo = $ruta === '/traslados' ? EjecutorOperaciones::TRASLADO : EjecutorOperaciones::ALTA;
        $respuesta = (new EjecutorOperaciones($this->dependencias))->ejecutar($tipo, $datos, $actorRefId);
        $estado = self::ESTADOS[$respuesta['codigo']];
        if (($respuesta['cuerpo']['motivo'] ?? null) === 'NO_AUTORIZADO') {
            $estado = 403;
        }

        return ['estado' => $estado === 200 && $ruta === '/traslados' ? 201 : $estado, 'cuerpo' => $respuesta['cuerpo']];
    }

    /** @return array{estado: int, cuerpo: array<string, mixed>} */
    private function consultar(string $actorRefId, string $estanciaId): array
    {
        try {
            $estanciaId = Identificador::exigir($estanciaId, 'estancia');
            [$registro, $autorizador] = ($this->dependencias)();
            $estado = (new ConsultarInternacion($registro, $autorizador))->porEstancia($actorRefId, $estanciaId);
        } catch (SolicitudInvalida) {
            return self::error(422, 'ESTANCIA_INVALIDA');
        } catch (NoAutorizado) {
            return self::error(403, 'NO_AUTORIZADO');
        } catch (FalloPersistencia) {
            return self::error(503, 'HOSPITAL_NO_DISPONIBLE');
        }
        if ($estado === null) {
            return self::error(404, 'ESTANCIA_INEXISTENTE');
        }

        return ['estado' => 200, 'cuerpo' => ['episodio_id' => $estado->episodioId, 'tipo' => $estado->tipoEpisodio, 'estado' => $estado->estadoEpisodio,
            'estancia_abierta_id' => $estado->estanciaAbiertaId, 'cama_actual_id' => $estado->camaActualId,
            'estancia_consultada_abierta' => $estado->estanciaAbiertaId === $estanciaId]];
    }

    /** @return array{estado: int, cuerpo: array<string, string>} */
    private static function error(int $estado, string $codigo): array
    {
        return ['estado' => $estado, 'cuerpo' => ['resultado' => 'error', 'codigo' => $codigo]];
    }
}
