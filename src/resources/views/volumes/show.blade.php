@extends('app')
@section('full-navbar', true)
@section('title', $volume->name)

@push('styles')
<link href="{{ cachebust_asset('vendor/label-trees/styles/main.css') }}" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="{{ cachebust_asset('vendor/geo/styles/ol.css') }}">
<link href="{{ cachebust_asset('vendor/geo/styles/main.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ cachebust_asset('vendor/label-trees/scripts/main.js') }}"></script>
<script src="{{ cachebust_asset('vendor/geo/scripts/ol.js') }}"></script>
<script src="{{ cachebust_asset('vendor/geo/scripts/main.js') }}"></script>
<script type="text/javascript">
    biigle.$declare('geo.images', {!! $images !!});
    biigle.$declare('geo.volume', {!! $volume !!});
    biigle.$declare('geo.labelTrees', {!! $trees !!});
    biigle.$declare('geo.overlays', {!! $overlays !!});
    biigle.$declare('geo.overlayUrl', '{!! url('api/v1/geo-overlays/:id/file') !!}');
</script>
@endpush

@section('content')
<main class="sidebar-container">
    <section id="volume-geo-map" class="sidebar-container__content">
        <image-map :images="images" :preselected="selectedImages" :selectable="true" v-on:select="handleSelectedImages" :overlays="overlays"></image-map>
    </section>
    <sidebar id="geo-sidebar" v-on:toggle="handleSidebarToggle" v-cloak>
        <sidebar-tab name="labels" icon="tags" title="Filter images by label">
            <label-trees :trees="labelTrees" :multiselect="true" v-on:select="handleSelect" v-on:deselect="handleDeselect" v-on:clear="handleCleared"></label-trees>
        </sidebar-tab>
    </sidebar>
</main>
@endsection

@section('navbar')
<div id="geo-navbar" class="navbar-text navbar-volumes-breadcrumbs">
    @include('volumes::partials.projectsBreadcrumb', ['projects' => $volume->projects]) / <a href="{{route('volume', $volume->id)}}">{{$volume->name}}</a> / <strong>Map</strong> @include('volumes::partials.annotationSessionIndicator') <span v-if="loading" class="loader loader--active"></span><span v-else v-cloak>(<span v-text="number"></span> images)</span>
</div>
@endsection
