# BIIGLE Geo Module

[![Test status](https://github.com/biigle/geo/workflows/Tests/badge.svg)](https://github.com/biigle/geo/actions?query=workflow%3ATests)

This is the BIIGLE module to explore images on a world map.

## Installation

This module is already included in [`biigle/biigle`](https://github.com/biigle/biigle).

1. Run `composer require biigle/geo`.
2. Add `Biigle\Modules\Geo\GeoServiceProvider::class` to the `providers` array in `config/app.php`.
3. Run `php artisan vendor:publish --tag=public` to publish the public assets of this module.

## Set up

Add geo-overlay disk to `config/filesystems.php`.

```php
'geo-overlays' => [
    'driver' => 'local',
    'root' => storage_path('app/public/geo-overlays'),
    'url' => env('APP_URL').'/storage/geo-overlays',
    'visibility' => 'public',
 ],
```

## Developing

Take a look at the [development guide](https://github.com/biigle/core/blob/master/DEVELOPING.md) of the core repository to get started with the development setup.

Want to develop a new module? Head over to the [biigle/module](https://github.com/biigle/module) template repository.

## Contributions and bug reports

Contributions to BIIGLE are always welcome. Check out the [contribution guide](https://github.com/biigle/core/blob/master/CONTRIBUTING.md) to get started.
