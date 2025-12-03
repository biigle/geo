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
            <tab title="geoTIFF" :disabled="loading">
                <geotiff-overlay-form :volume-id="{{$volume->id}}" v-on:success="addOverlay" v-on:upload="handleUpload" v-slot="{ submitGeoTiff, uploadGeoTiff, error }">
                    @include('geo::volumes.edit.geotiffOverlayForm')
                </geotiff-overlay-form>
            </tab>
            <tab title="WMS" :disabled="loading">
                <webmap-overlay-form :volume-id="{{$volume->id}}"  v-on:success="addOverlay" v-on:upload="handleUpload" v-slot="{ submitWebMap, error }">
                    @include('geo::volumes.edit.webmapOverlayForm')
                </webmap-overlay-form>
            </tab>
        </tabs>
    </div>
    <div v-if="hasOverlays('geoOverlays')">
        <overlay-table :overlays="geoOverlays" v-on:remove="handleRemove" :volume-id="{{ $volume->id }}" :project-id="{{ $volume->projects->pluck('id')->first() }}" >
        <template v-slot:header>
            <th></th>
            <th>#</th>
            <th>Filename</th>
            <th>Browsing</th>
            <th>Delete</th>
        </template>
        </overlay-table>
    </div>
    <div v-else>
        <ul class="list-group" v-cloak>
            <li class="list-group-item text-muted">This volume has no geo overlays. <a v-if="!editing" href="#" v-on:click.prevent="toggleEditing">Add some.</a></li>
        </ul>
    </div>
</div>
@endif