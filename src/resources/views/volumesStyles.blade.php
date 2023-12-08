@if ($volume->isImageVolume() && $volume->hasGeoInfo())
<link href="{{ cachebust_asset('vendor/geo/styles/main.css') }}" rel="stylesheet">
@endif
