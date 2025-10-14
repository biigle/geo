@if ($volume->isImageVolume() && $volume->hasGeoInfo())
@push('scripts')
    {{vite_hot(base_path('vendor/biigle/geo/hot'), ['src/resources/assets/js/volumes/volumes.js'], 'vendor/geo')}}
    <script type="module">
        biigle.$declare('volumes.geoOverlays', {!! \Biigle\Modules\Geo\GeoOverlay::where('volume_id', $volume->id)->get() !!});
    </script>
    {{-- <script src="{{ cachebust_asset('vendor/geo/scripts/volumes.js') }}"></script> --}}
@endpush
@endif