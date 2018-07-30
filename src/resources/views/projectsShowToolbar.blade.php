@if ($project->hasGeoInfo())
<a href="{{route('project-geo', $project->id)}}" class="btn btn-default" title="Show project images on a world map"><span class="fa fa-globe" aria-hidden="true"></span> Project map</a>
@endif
