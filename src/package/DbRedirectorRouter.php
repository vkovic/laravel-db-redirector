<?php

namespace Vkovic\LaravelDbRedirector;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Vkovic\LaravelDbRedirector\Models\RedirectRule;

class DbRedirectorRouter
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * DbRedirector Router constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param Request $request
     *
     * @return Response|null
     */
    public function getRedirectFor(Request $request)
    {
        $potentialRules = $this->getPotentialRules($request->path());

        // Make route for each of potential rules and let Laravel handle the rest
        $potentialRules->each(function ($redirect) {
            $this->router->get($redirect->origin, function () use ($redirect) {
                $destination = $this->resolveDestination($redirect->destination);

                return redirect($destination, $redirect->status_code);
            });
        });

        // If one of the routes could be dispatched it means
        // we have a match in database and we can continue with redirection
        // (from callback above)
        try {
            return $this->router->dispatch($request);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get potential rules based on requested URI
     *
     * @param string $uri
     *
     * @return Collection
     */
    public function getPotentialRules($uri)
    {
        //
        // Try to match uri with rule without route params
        //

        $redirectRules = RedirectRule::where('origin', $uri)->get();

        if ($redirectRules->isNotEmpty()) {
            return $redirectRules;
        }

        //
        // Try to match uri with rule with url params:
        //

        // Search only rules with params but without optional params
        $query = RedirectRule::where('origin', 'LIKE', '%{%')
            ->where('origin', 'NOT LIKE', '%?}%');

        // Narrow potential matches by matching number of url segments
        // (by matching number of slashes)
        $slashesCount = substr_count($uri, '/');
        $rawWhere = \DB::raw("LENGTH(origin) - LENGTH(REPLACE(origin, '/', ''))");
        $query = $query->where($rawWhere, $slashesCount);

        // Ordering
        $query
            // Route with lesser number of parameters will have top priority
            ->orderByRaw("LENGTH(origin) - LENGTH(REPLACE(origin, '{', ''))")
            // Rules with params nearer end of route will have last priority
            ->orderByRaw("INSTR(origin, '{') DESC");

        // Get collection of potential rules
        $potentialRules = $query->get();

        if ($potentialRules->isNotEmpty()) {
            return $potentialRules;
        }

        //
        // Try to match uri with rule with optional url params:
        //

        // Search only rules with optional params
        $query = RedirectRule::where('origin', 'LIKE', '%?}%');

        // Ordering
        $query
            // Route with less segments will have top priority
            ->orderByRaw("LENGTH(origin) - LENGTH(REPLACE(origin, '/', ''))")
            // Route with lesser number of parameters will have next priority
            ->orderByRaw("LENGTH(origin) - LENGTH(REPLACE(origin, '{', ''))")
            // Rules with params nearer end of route will have last priority
            ->orderByRaw("INSTR(origin, '{') DESC");

        return $query->get();
    }

    /**
     * Resolve destination by replacing parameters from
     * current route into destination rule
     *
     * @param string $destination
     *
     * @return mixed
     */
    protected function resolveDestination($destination)
    {
        foreach ($this->router->getCurrentRoute()->parameters() as $key => $value) {
            $destination = str_replace("{{$key}}", $value, $destination);
        }

        // Remove non existent optional params from destination
        // but after existent params has been resolved
        $destination = preg_replace('/\/{[\w-_]+}/', '', $destination);

        return $destination;
    }
}