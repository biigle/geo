@if ($project->hasGeoInfo())
    <li role="presentation">
        <a href="{{route('project-geo', $project->id)}}" title="Show project images on a world map"><i
                class="fa fa-globe"></i> Map</a>
    </li>
@endif
