<?php

namespace Vkovic\LaravelDbRedirector\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Vkovic\LaravelDbRedirector\DbRedirectorRouter;

class DbRedirectorMiddleware
{
    /**
     * @var Router
     */
    protected $router;

    public function handle(Request $request, Closure $next)
    {
        $dbRedirectorRouter = app(DbRedirectorRouter::class);
        $redirectResponse = $dbRedirectorRouter->getRedirectFor($request);

        return $redirectResponse ?? $next($request);
    }
}