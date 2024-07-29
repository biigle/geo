@if ($volume->isImageVolume() && $volume->hasGeoInfo())
<script type="text/javascript">
        biigle.$declare('geo.browsingOverlays', {!! \Biigle\Modules\Geo\GeoOverlay::where('volume_id', $volume->id)->where('browsing_layer', true)->get() !!});
        biigle.$declare('geo.projectId', {!! $volume->projects->pluck('id')->first() !!});
</script>
<script src="{{ cachebust_asset('vendor/geo/scripts/volumes.js') }}"></script>
@endif
