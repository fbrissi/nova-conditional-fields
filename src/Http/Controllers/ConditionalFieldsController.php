<?php

namespace Firework\NovaConditionalFields\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class ConditionalFieldsController extends Controller
{
    public function index(NovaRequest $request)
    {
        $field = $request->newResource()
            ->availableFields($request)
            ->firstWhere('attribute', $request->field);

        $resolveCallback = $field->resolveCallbacks[$request->conditional];
        $callback = $resolveCallback['callback'];
        $resourceClass = $resolveCallback['resourceName'];
        $modelOrValue = $field->getModelOrValue($request, $resourceClass);

        return ($callback)($modelOrValue);
    }
}
