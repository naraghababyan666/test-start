<?php

namespace App\Http\Middleware;

use Closure;

class DetectLocale
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param mixed ...$locales
     * @return mixed
     */
    public function handle($request, Closure $next, ...$locales)
    {
        $locales = $request["language_code"] ? [$request["language_code"]] : ['hy'];
        if ($language = $request->getPreferredLanguage($locales)) {
            app()->setLocale($language);
        }
        return $next($request);
    }
}
