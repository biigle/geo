@if ($volume->isImageVolume() && $volume->hasGeoInfo())
{{vite_hot(base_path('vendor/biigle/geo/hot'), ['src/resources/assets/js/volumes/main.js'], 'vendor/geo')}}
@endif
