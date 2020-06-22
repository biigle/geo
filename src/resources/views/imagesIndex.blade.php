@if ($image->lng !== null && $image->lat !== null)

@push('scripts')
<script src="{{ cachebust_asset('vendor/geo/scripts/main.js') }}"></script>
<script type="text/javascript">
    biigle.$declare('geo.image', {!! $image !!});
</script>
@endpush

@push('styles')
<link href="{{ cachebust_asset('vendor/geo/styles/main.css') }}" rel="stylesheet">
@endpush

<div class="col-lg-12">
    <div id="geo-image-location-panel" class="panel panel-default">
        <div class="panel-heading">
            Location
            <span class="pull-right">
                <a href="{{route('volume-geo', $volume->id) }}" title="Show all volume images on a world map" class="btn btn-default btn-xs">show all</a>
            </span>
        </div>
        <image-map :images="images" :interactive="false" :zoom="2"></image-map>
    </div>
</div>

@endif
