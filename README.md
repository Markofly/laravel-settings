# Laravel settings
[![Packagist](https://img.shields.io/packagist/v/markofly/laravel-settings.svg)](https://packagist.org/packages/markofly/laravel-settings)
[![Packagist](https://img.shields.io/packagist/dt/markofly/laravel-settings.svg)](https://packagist.org/packages/markofly/laravel-settings)
[![Packagist](https://img.shields.io/packagist/l/markofly/laravel-settings.svg)](http://choosealicense.com/licenses/mit)

laravel-settings is Laravel 5 package.

## Installation

Using composer

```bash
$ composer require markofly/laravel-settings
```

Add the service provider to `config/app.php`

```php
'providers' => [
  ...
  Markofly\Settings\SettingsServiceProvider::class,
],
```

Add the facade to `config/app.php`

```php
'aliases' => [
  ...
  'Settings' => \Markofly\Settings\Facades\Settings::class,
],
```

Publish config and migration files

```bash
$ php artisan vendor:publish --provider="Markofly\Settings\SettingsServiceProvider"
```

```bash
$ php artisan migrate
```

## Usage

In config/markofly/settings.php create default settings.

```php
<?php

return [
    ...
    'fields' => [
            'site_name' => [
                'default' => 'Laravel 5',
            ],
            ...
        ],
];
```

Get setting value

```php
<?php
Settings::get('site_name');
Settings::get('site_name', 'Default value');
```

Save settings to database

```php
<?php
Settings::save('site_name', 'Laravel 5');
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
