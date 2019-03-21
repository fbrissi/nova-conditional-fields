
# Nova Field Conditional

[![Latest Version on Packagist]()]()
[![Total Downloads]()]()
[![License]()]()

### Description

A component for grouping fields that depend on other field values.

### Installation

The package can be installed through Composer.

```bash
composer require firework/nova-conditional-fields
```

### Usage

1. Add the `Firework\NovaConditionalFields\HasDependencies` trait to your Nova Resource.
2. Add the `Firework\NovaConditionalFields\Conditional` to your Nova Resource `fields` method.

```php
class Page extends Resource
{
    public function fields(Request $request)
    {
        return [
            
            BelongsTo::make(__('Document'), 'document')
                ->rules('required'),

            Conditional::make('document-value', [
                Text::make(__('Value'), 'value')
                    ->rules('max:255'),
            ])->when('document', function ($model) {
                return $model->value;
            }),

            Conditional::make('document-link', [
                Text::make(__('Link'), 'link')
                    ->rules('max:255'),
            ])->when('document', function ($model) {
                return $model->link;
            }),

            Conditional::make('document-attachment', [
                File::make('Attachment')
                    ->rules('file')
                    ->disk('public'),
            ])->when('document', function ($model) {
                return $model->attachment;
            }),

        ];
    }
}
```

### Dependencies

The package supports two kinds of dependencies:

1. `->when('field', 'value or Closure')`
2. `->whenNotEmpty('field')`

The fields used as dependencies can by of any of the default Laravel Nova field types.

### License

The MIT License (MIT). Please see [License File]() for more information.
