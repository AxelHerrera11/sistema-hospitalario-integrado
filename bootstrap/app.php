<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            // Nombre propio: el paquete tymon/jwt-auth registra su propio 'jwt.auth' y lo sobrescribía,
            // por lo que la validación token ↔ X-Tenant-ID nunca se ejecutaba.
            'auth.jwt' => \App\Http\Middleware\JwtAuth::class,
            'jwt.refresh' => \Tymon\JWTAuth\Http\Middleware\RefreshToken::class,
            // Spatie Laravel Permission (uso: role:Admin|Médico, permission:pacientes.ver)
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // 'tenant' debe correr ANTES que SubstituteBindings (grupo 'api'): si no, el
        // binding implícito ({appointment}, {doctor}, ...) resuelve el modelo sin
        // currentTenant, el global scope de BelongsToTenant no se aplica y un id de
        // otro hospital se puede leer o modificar. Cubierto en TenantIsolationTest.
        $middleware->prependToPriorityList(
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\TenantMiddleware::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Respuestas JSON uniformes cuando falta rol o permiso.
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'No tiene permiso para realizar esta acción.',
                ], 403);
            }
        });

        // 404 genérico: un id inexistente, de otro hospital o una ruta que no existe
        // responden igual, sin exponer la clase del modelo ("No query results for
        // model [App\Models\...]"). Laravel ya convirtió ModelNotFoundException aquí.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Recurso no encontrado.',
                ], 404);
            }
        });
    })->create();
