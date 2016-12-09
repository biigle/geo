@extends('app')

@section('title'){{ $transect->name }}@stop

@push('styles')
<link rel="stylesheet" type="text/css" href="{{ cachebust_asset('vendor/geo/styles/ol.css') }}">
<link href="{{ cachebust_asset('vendor/geo/styles/main.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ cachebust_asset('vendor/geo/scripts/ol.js') }}"></script>
<script src="{{ cachebust_asset('vendor/geo/scripts/main.js') }}"></script>
<script type="text/javascript">
    biigle.geo.images = {!! $images !!};
    biigle.geo.transect = {!! $transect !!};
</script>
@endpush

@section('content')
<main class="geo__container">
    <section id="geo-map" class="geo__map">
        <image-map :images="images" :preselected="selectedImages" :selectable="true" v-on:select="handleSelectedImages"></image-map>
    </section>
    <aside class="geo__sidebar">

    </aside>
</main>
@endsection

@section('navbar')
<div class="navbar-text navbar-transects-breadcrumbs">
    @include('transects::partials.projectsBreadcrumb', ['projects' => $transect->projects]) / <a href="{{route('transect', $transect->id)}}">{{$transect->name}}</a> / <strong>Map</strong> @include('transects::partials.annotationSessionIndicator')
</div>
@endsection
