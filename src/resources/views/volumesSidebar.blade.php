@if ($volume->isImageVolume() && $volume->hasGeoInfo())
    <sidebar-tab name="geo" icon="globe" title="Show volume images on a world map" href="{{ route('volume-geo', $volume->id) }}"></sidebar-tab>
@endif
