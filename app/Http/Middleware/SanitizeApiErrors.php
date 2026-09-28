<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeApiErrors
{
    public function handle(Request $request, Closure $next): Response
    {
        return self::sanitize($next($request), $request);
    }

    public static function sanitize(Response $response, Request $request): Response
    {
        if (! $request->is('api/*') || $response->getStatusCode() < 500) {
            return $response;
        }

        $data = json_decode($response->getContent() ?: '{}', true);
        if ($response->getStatusCode() === 503 && ($data['maintenance_mode'] ?? false) === true) {
            return $response;
        }

        // Conservar encabezados (CORS, cookies, Retry-After); nunca enviar SQL ni trazas.
        $response->setContent(json_encode([
            'message' => 'No se pudo completar la solicitud. Inténtalo nuevamente más tarde.',
        ], JSON_UNESCAPED_UNICODE));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->remove('Content-Length');

        return $response;
    }
}
