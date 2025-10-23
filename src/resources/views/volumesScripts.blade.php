@if ($volume->isImageVolume() && $volume->hasGeoInfo())
<script src="{{ cachebust_asset('vendor/geo/scripts/volumes.js') }}"></script>
{{vite_hot(base_path('vendor/biigle/geo/hot'), ['src/resources/assets/js/volumes/volumes.js'], 'vendor/geo')}}
@endif
