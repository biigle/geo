@if ($transect->hasGeoInfo())
<a href="{{route('transect-geo', $transect->id)}}" class="btn btn-default transect-menubar__item" title="Show transect images on a world map">
    <span class="glyphicon glyphicon-globe" aria-hidden="true"></span>
</a>
@endif
