@if ($volume->isImageVolume() && $volume->hasGeoInfo())
<div id="volume-geo-overlay-upload" class="panel panel-default" :class="classObject">
    <div class="panel-heading">
        Geo overlays
        <span class="pull-right">
            <loader :active="loading"></loader>
            <button class="btn btn-default btn-xs" title="Add geo overlays" v-on:click="toggleEditing" :class="{active: editing}"><span class="fa fa-plus" aria-hidden="true"></span></button>
        </span>
    </div>
    <div class="panel-body" v-if="editing" v-cloak>
        <tabs>
            {{-- <tab header="Plain" title="Upload a geo overlay in plain format" :disabled="loading">
                <plain-overlay-form inline-template v-on:loading-start="startLoading" v-on:error="finishLoading" v-on:success="addOverlay">
                    @include('geo::volumes.edit.plainOverlayForm')
                </plain-overlay-form>
            </tab> --}}
            <tab title="geoTIFF" :disabled="loading">
                <geotiff-overlay-form inline-template :volume-id="{{$volume->id}}" v-on:success="addOverlay">
                    @include('geo::volumes.edit.geotiffOverlayForm')
                </geotiff-overlay-form>
            </tab>
            <tab title="WMS" :disabled="loading">
                <p>Embed a geo overlay from a web-map-service link</p>
            </tab>
        </tabs>
    </div>
    <geotiff-overlay-table :overlays="overlays" v-on:remove="handleRemove" :volume-id="{{ $volume->id }}"></geotiff-overlay-table>
    <ul class="list-group" v-cloak>
        <li class="list-group-item text-muted" v-if="!hasOverlays">This volume has no geo overlays. <a v-if="!editing" href="#" v-on:click.prevent="toggleEditing">Add some.</a></li>
    </ul>
</div>
@endif