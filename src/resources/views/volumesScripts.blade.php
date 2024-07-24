@if ($volume->isImageVolume() && $volume->hasGeoInfo())
<script type="text/javascript">
        biigle.$declare('geo.overlayUrl', '{!! url('api/v1/geo-overlays/:id/file') !!}');
</script>
<script src="{{ cachebust_asset('vendor/geo/scripts/volumes.js') }}"></script>
@endif
