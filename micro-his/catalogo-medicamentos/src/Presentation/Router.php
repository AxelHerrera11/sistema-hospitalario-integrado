<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Presentation;

/**
 * Controlador frontal de la entrada MVC: elige el controlador por método y ruta y valida solo la FORMA de la
 * petición (JSON, tipos). No tiene SQL ni reglas de negocio: esas viven en Application y Domain.
 */
final readonly class Router
{
    private const DRAFT_FIELDS = ['code', 'name', 'active_ingredient', 'pharmacological_group'];

    public function __construct(
        private RegistrationController $registration,
        private SearchController $search,
        private ValidityController $validity,
    ) {
    }

    /** @return array{status: int, body: array} */
    public function dispatch(Request $request): array
    {
        if ($request->path === '/medications') {
            return match ($request->method) {
                'POST' => $this->withJson($request, self::DRAFT_FIELDS, fn (array $body): array => $this->registration->register($body)),
                'GET' => is_string($request->query['q'] ?? null)
                    ? $this->search->search($request->query['q'], $request->query['limit'] ?? null)
                    : self::error(422, 'MEDICATION_INVALID', 'El parámetro q es obligatorio.', 'term'),
                default => self::error(405, 'METHOD_NOT_ALLOWED', 'Método no permitido.'),
            };
        }
        if (preg_match('#^/medications/([^/]+)/validity$#', $request->path, $match) === 1) {
            return match ($request->method) {
                'GET' => $this->search->validity(rawurldecode($match[1])),
                'PATCH' => $this->withJson($request, ['validity'], fn (array $body): array =>
                    $this->validity->change(rawurldecode($match[1]), $body['validity'] ?? '')),
                default => self::error(405, 'METHOD_NOT_ALLOWED', 'Método no permitido.'),
            };
        }

        return self::error(404, 'ROUTE_NOT_FOUND', 'Ruta no encontrada.');
    }

    /** Decodifica un objeto JSON, rechaza los campos desconocidos y exige que los conocidos, si vienen, sean texto. */
    private function withJson(Request $request, array $fields, callable $handler): array
    {
        $body = json_decode($request->rawBody, true);
        if (!is_array($body) || array_is_list($body) && $body !== []) {
            return self::error(400, 'BODY_INVALID', 'El cuerpo debe ser un objeto JSON.');
        }
        foreach (array_keys($body) as $field) {
            if (!in_array($field, $fields, true)) {
                return self::error(422, 'MEDICATION_INVALID', "El campo {$field} no se admite.", (string) $field);
            }
        }
        foreach ($fields as $field) {
            if (array_key_exists($field, $body) && !is_string($body[$field])) {
                return self::error(422, 'MEDICATION_INVALID', "El campo {$field} debe ser texto.", $field);
            }
        }

        return $handler($body);
    }

    private static function error(int $status, string $code, string $message, ?string $field = null): array
    {
        return ['status' => $status, 'body' => array_filter(['error' => $code, 'field' => $field, 'message' => $message], static fn ($v) => $v !== null)];
    }
}
