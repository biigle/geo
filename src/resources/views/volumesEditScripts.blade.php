@if ($volume->isImageVolume() && $volume->hasGeoInfo())
@push('scripts')
    <script type="text/javascript">
        biigle.$declare('volumes.geoOverlays', {!! \Biigle\Modules\Geo\GeoOverlay::where('volume_id', $volume->id)->get() !!});
    </script>
    <script src="{{ cachebust_asset('vendor/geo/scripts/volumes.js') }}"></script>
@endpush
@endif