# Biigle Geo Module

Install the module:

Add the following to the repositories array of your `composer.json`:
```
{
  "type": "vcs",
  "url": "git@github.com:biigle/geo.git"
}
```

1. Run `php composer.phar require biigle/geo`.
2. Add `'Biigle\Modules\Geo\GeoServiceProvider'` to the `providers` array in `config/app.php`.
3. Run `php artisan geo:publish` to refresh the public assets of this package. Do this for every update of the package.
