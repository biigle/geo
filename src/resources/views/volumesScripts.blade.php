@if ($volume->isImageVolume() && $volume->hasGeoInfo())
<script type="module">
        biigle.$declare('geo.geoOverlays', {!! \Biigle\Modules\Geo\GeoOverlay::where('volume_id', $volume->id)->where('browsing_layer', true)->orderBy('layer_index')->get() !!});
        biigle.$declare('geo.projectId', {!! $volume->projects->pluck('id')->first() !!});
        biigle.$declare('geo.overlayUrlTemplate', '{!! Storage::disk(config('geo.tiles.overlay_storage_disk'))->url(':id/:id_tiles/') !!}');
</script>
<script src="{{ cachebust_asset('vendor/geo/scripts/volumes.js') }}"></script>
{{vite_hot(base_path('vendor/biigle/geo/hot'), ['src/resources/assets/js/volumes/volumes.js'], 'vendor/geo')}}
@endif
