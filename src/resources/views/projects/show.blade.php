@extends('app')
@section('full-navbar', true)
@section('title', $project->name)

@push('styles')
{{vite_hot(base_path('vendor/biigle/geo/hot'), ['src/resources/assets/sass/main.scss'], 'vendor/geo')}}
@endpush

@push('scripts')
{{vite_hot(base_path('vendor/biigle/geo/hot'), ['src/resources/assets/js/geo/main.js'], 'vendor/geo')}}
<script type="module">
    biigle.$declare('geo.images', {!! $images !!});
    biigle.$declare('geo.project', {!! $project !!});
    biigle.$declare('geo.labelTrees', {!! $trees !!});
</script>
@endpush

@section('content')
<main id="project-geo-map" class="sidebar-container">
    <section class="sidebar-container__content">
        <image-map :images="images"></image-map>
    </section>
    <sidebar v-on:toggle="handleSidebarToggle" v-cloak>
        <sidebar-tab name="labels" icon="tags" title="Filter images by label">
            <label-trees
                :trees="labelTrees"
                :multiselect="true"
                v-on:select="handleSelectedLabel"
                v-on:deselect="handleDeselectedLabel"
                v-on:clear="handleClearedLabels"
                ></label-trees>
        </sidebar-tab>
    </sidebar>
</main>
@endsection

@section('navbar')
<div id="geo-navbar" class="navbar-text navbar-volumes-breadcrumbs">
    <a href="{{route('project', $project->id)}}" class="navbar-link" title="Show project {{$project->name}}">{{$project->name}}</a> / <strong>Map</strong> <span v-if="loading" class="loader loader--active"></span><span v-else v-cloak>(<span v-text="number"></span> images)</span>
</div>
@endsection
