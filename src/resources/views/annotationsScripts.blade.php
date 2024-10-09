<script type="text/javascript">
    biigle.$declare('annotations.overlays', {!! \Biigle\Modules\Geo\GeoOverlay::where('volume_id', $volume->id)->where('context_layer', true)->orderBy('layer_index')->get() !!});
    biigle.$declare('annotations.overlayUrlTemplate', '{!! Storage::disk(config('geo.tiles.overlay_storage_disk'))->url(':id/:id_tiles/') !!}');

</script>
<script src="{{ cachebust_asset('vendor/geo/scripts/annotations.js') }}"></script>