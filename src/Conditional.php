<?php

namespace Firework\NovaConditionalFields;

use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;
use Laravel\Nova\Http\Requests\NovaRequest;

class Conditional extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nova-conditional-fields';


    /**
     * Show in index
     *
     * @var bool
     */
    public $showOnIndex = false;

    /**
     * The URI key of the related resource.
     *
     * @var string
     */
    public $resourceName;

    /**
     * The array resolve callback.
     *
     * @var array
     */
    public $resolveCallbacks = [];

    /**
     * Conditional constructor.
     *
     * @param $name
     * @param $fields
     * @param null $attribute
     * @param null $resolveCallback
     */
    public function __construct($name, $fields, $attribute = null, $resolveCallback = null)
    {
        parent::__construct('conditional-' . $name, $attribute, $resolveCallback);

        $this->withMeta(['fields' => $fields]);
        $this->withMeta(['dependencies' => []]);
    }

    /**
     * Adds a dependency
     *
     * @param $field
     * @param $value
     * @return $this
     */
    public function when($field, $value)
    {
        $resourceName = ResourceRelationshipGuesser::guessResource($field);

        if ($value instanceof Closure) {
            $this->resolveCallbacks[$field] = ['callback' => $value, 'resourceName' => $resourceName];
            $this->withMeta([
                'dependencies' => array_merge($this->meta['dependencies'], [['field' => $field, 'callback' => true]]),
            ]);
        } else {
            $this->withMeta([
                'dependencies' => array_merge($this->meta['dependencies'], [['field' => $field, 'value' => $value]]),
            ]);
        }

        return $this;
    }

    /**
     * Adds not empty dependency
     *
     * @param $field
     * @return $this
     */
    public function whenNotEmpty($field)
    {
        return $this->withMeta([
            'dependencies' => array_merge($this->meta['dependencies'], [['field' => $field, 'notEmpty' => true]])
        ]);
    }

    /**
     * Build an associatable query for the field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getModelOrValue(NovaRequest $request, $resourceClass)
    {
        $model = forward_static_call(
            [$resourceClass, 'newModel']
        );

        $model = $model->newQueryWithoutScopes()->whereKey($request->value)->first();

        return $model ?? $request->value;
    }

    /**
     * @param mixed $resource
     * @param null $attribute
     */
    public function resolveForDisplay($resource, $attribute = null)
    {
        parent::resolveForDisplay($resource, $attribute);

        foreach ($this->meta['dependencies'] as $index => $dependency) {
            if (array_key_exists('notEmpty', $dependency) && !empty($resource->{$dependency['field']})) {
                $this->meta['dependencies'][$index]['satisfied'] = true;
            } else {
                $dependecyValue = $resource->{$dependency['field']};

                if (array_key_exists('callback', $dependency)) {
                    $value = ($this->resolveCallbacks[$dependency['field']]['callback'])($dependecyValue);
                } else if (array_key_exists('value', $dependency)) {
                    $value = $dependency['value'];
                }

                if ($value || $dependecyValue === $value) {
                    $this->meta['dependencies'][$index]['satisfied'] = true;
                }
            }
        }
    }

    /**
     * Retrieve values of dependency fields
     *
     * @param mixed $resource
     * @param string $attribute
     * @return array|mixed
     */
    protected function resolveAttribute($resource, $attribute)
    {
        foreach ($this->meta['fields'] as $field) {
            $field->resolve($resource);
        }
        return [];
    }

    /**
     * Fills the attributes of the model within the container if the dependencies for the container are satisfied.
     *
     * @param NovaRequest $request
     * @param string $requestAttribute
     * @param object $model
     * @param string $attribute
     */
    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        foreach ($this->meta['fields'] as $field) {
            $field->fill($request, $model);
        }
    }
}
