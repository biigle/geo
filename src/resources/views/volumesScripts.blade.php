@if ($volume->isImageVolume() && $volume->hasGeoInfo())
<script type="text/javascript">
        biigle.$declare('geo.geoOverlays', {!! \Biigle\Modules\Geo\GeoOverlay::where('volume_id', $volume->id)->where('browsing_layer', true)->orderByDesc('layer_index')->get() !!});
        biigle.$declare('geo.projectId', {!! $volume->projects->pluck('id')->first() !!});
</script>
<script src="{{ cachebust_asset('vendor/geo/scripts/volumes.js') }}"></script>
@endif
