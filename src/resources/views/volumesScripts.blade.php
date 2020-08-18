@if ($volume->isImageVolume() && $volume->hasGeoInfo())
<script src="{{ cachebust_asset('vendor/geo/scripts/volumes.js') }}"></script>
@endif
