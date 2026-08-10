<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Honeypot + time-trap para os formularios publicos, que ficam abertos e sem
 * senha. Bots preenchem todos os campos (inclusive o oculto) e enviam quase
 * instantaneamente; humanos nao fazem nem uma coisa nem outra.
 */
class ProtectPublicForm
{
    public const HONEYPOT_FIELD = 'atpg_website';

    public const TIMESTAMP_FIELD = 'atpg_loaded_at';

    private const MINIMUM_SECONDS = 3;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        if (filled($request->input(self::HONEYPOT_FIELD)) || $this->submittedTooFast($request)) {
            Log::warning('Envio de formulario publico bloqueado por suspeita de bot.', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            throw ValidationException::withMessages([
                'name' => 'Não foi possível validar o envio. Recarregue a página e tente novamente.',
            ]);
        }

        return $next($request);
    }

    private function submittedTooFast(Request $request): bool
    {
        $loadedAt = $request->input(self::TIMESTAMP_FIELD);

        if (! is_numeric($loadedAt)) {
            return false;
        }

        return (time() - (int) $loadedAt) < self::MINIMUM_SECONDS;
    }
}
