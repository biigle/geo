@if ($volume->hasGeoInfo())
<script data-ng-controller="GeoFilterController" type="text/ng-template" id="geoFilterRule.html">
@{{rule.filter.name}}
</script>
@endif
