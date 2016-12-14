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
    biigle.$declare('geo.images', {!! $images !!});
    biigle.$declare('geo.transect', {!! $transect !!});
    biigle.$declare('geo.labelTrees', {!! $trees !!});
</script>
@endpush

@section('content')
<main class="geo__container">
    <section id="geo-map" class="geo__map">
        <image-map :images="images" :preselected="selectedImages" :selectable="true" v-on:select="handleSelectedImages"></image-map>
    </section>
    <sidebar id="geo-sidebar" class="geo__sidebar" v-on:toggle="handleSidebarToggle">
        <sidebar-tab v-cloak slot="tabs" name="labels" icon="tags" title="Show label trees">
            <label-trees v-bind:trees="labelTrees" v-on:select="handleSelect"></label-trees>
        </sidebar-tab>
    </sidebar>
</main>
@endsection

@section('navbar')
<div id="geo-navbar" class="navbar-text navbar-transects-breadcrumbs">
    @include('transects::partials.projectsBreadcrumb', ['projects' => $transect->projects]) / <a href="{{route('transect', $transect->id)}}">{{$transect->name}}</a> / <strong>Map</strong> @include('transects::partials.annotationSessionIndicator') <span v-if="loading" class="loader loader--active"></span><span v-else v-cloak>(<span v-text="number"></span> images)</span>
</div>
@endsection
