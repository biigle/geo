@if ($image->lng !== null && $image->lat !== null)

@push('scripts')
{{vite_hot(base_path('vendor/biigle/geo/hot'), ['src/resources/assets/js/geo/main.js'], 'vendor/geo')}}
<script type="module">
    biigle.$declare('geo.image', {!! $image !!});
</script>
@endpush

@push('styles')
{{vite_hot(base_path('vendor/biigle/geo/hot'), ['src/resources/assets/sass/main.scss'], 'vendor/geo')}}
@endpush

<div class="col-lg-12">
    <div id="geo-image-location-panel" class="panel panel-default">
        <div class="panel-heading">
            Location
            <span class="pull-right">
                <a href="{{route('volume-geo', $volume->id) }}" title="Show all volume images on a world map" class="btn btn-default btn-xs">show all</a>
            </span>
        </div>
        <image-map class="image-map--index" :images="images" :interactive="false" :zoom="2"></image-map>
    </div>
</div>

@endif
