@if ($volume->isImageVolume() && $volume->hasGeoInfo())
<script type="text/javascript">
        biigle.$declare('geo.geoOverlays', {!! \Biigle\Modules\Geo\GeoOverlay::where('volume_id', $volume->id)->where('browsing_layer', true)->orderBy('layer_index')->get() !!});
        biigle.$declare('geo.projectId', {!! $volume->projects->pluck('id')->first() !!});
        biigle.$declare('geo.overlayUrlTemplate', '{!! url('api/v1/volumes/:id/geo-overlays/geotiff/url') !!}');
</script>
<script src="{{ cachebust_asset('vendor/geo/scripts/volumes.js') }}"></script>
@endif
