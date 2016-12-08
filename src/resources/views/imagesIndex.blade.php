@if ($image->lng !== null && $image->lat !== null)

@push('scripts')
<script src="{{ cachebust_asset('vendor/geo/scripts/ol.js') }}"></script>
<script src="{{ cachebust_asset('vendor/geo/scripts/main.js') }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" type="text/css" href="{{ cachebust_asset('vendor/geo/styles/ol.css') }}">
@endpush

<div class="col-lg-12">
    <div id="image-location-panel" class="panel panel-default">
        <div class="panel-heading">
            Location
            <span class="pull-right">
                {{$image->lng}}, {{$image->lat}}
            </span>
        </div>
        <single-image-map :lng="{{$image->lng}}" :lat="{{$image->lat}}"></single-image-map>
    </div>
</div>

@endif
