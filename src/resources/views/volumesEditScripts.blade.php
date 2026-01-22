@if ($volume->isImageVolume() && $volume->hasGeoInfo())
    @push('scripts')
        {{vite_hot(base_path('vendor/biigle/geo/hot'), ['src/resources/assets/js/volumes/volumes.js'], 'vendor/geo')}}
        <script type="module">
            biigle.$declare('volumes.volumeId', {{ $volume->id }});
        </script>
    @endpush
@endif
