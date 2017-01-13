@if ($volume->hasGeoInfo())
<a href="{{route('volume-geo', $volume->id)}}" class="btn btn-default volume-menubar__item" title="Show volume images on a world map">
    <span class="glyphicon glyphicon-globe" aria-hidden="true"></span>
</a>
@endif
