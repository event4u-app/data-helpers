# Symfony Recipe for event4u/data-helpers

This recipe automatically configures the Data Helpers package for Symfony applications.

## What it does

- Registers the `DataHelpersBundle` in `config/bundles.php`
- Copies the default configuration to `config/packages/data_helpers.yaml`

## Manual Installation

If Symfony Flex is not available, you can manually copy the configuration:

```bash
cp vendor/event4u/data-helpers/config/symfony/data_helpers.yaml config/packages/
```

And register the bundle in `config/bundles.php`:

```php
return [
    // ...
    event4u\DataHelpers\Symfony\DataHelpersBundle::class => ['all' => true],
];
```

