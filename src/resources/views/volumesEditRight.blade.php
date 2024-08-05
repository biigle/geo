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
                <overlay-table :overlays="geotiffOverlays" v-on:remove="handleRemove" :volume-id="{{ $volume->id }}" :project-id="{{ $volume->projects->pluck('id')->first() }}" >
                    <template v-slot:title>GeoTIFF Overlays</template>
                    <template v-slot:header>
                        <th></th>
                        <th>#</th>
                        <th>Filename</th>
                        <th>Browsing</th>
                        <th>Context</th>
                        <th>Delete</th>
                    </template>
                </overlay-table>
            </tab>
            <tab title="WMS" :disabled="loading">
                <webmap-overlay-form inline-template :volume-id="{{$volume->id}}"  v-on:success="addOverlay">
                @include('geo::volumes.edit.webmapOverlayForm')
                </webmap-overlay-form>
                <overlay-table :overlays="webmapOverlays" v-on:remove="handleRemove" :volume-id="{{ $volume->id }}" :project-id="{{ $volume->projects->pluck('id')->first() }}" >
                    <template v-slot:title>WebMap Overlays</template>
                    <template v-slot:header>
                        <th></th>
                        <th>#</th>
                        <th>Filename</th>
                        <th>Browsing</th>
                        <th>Context</th>
                        <th>Delete</th>
                    </template>
                </overlay-table>
            </tab>
        </tabs>
    </div>
    <div v-else>
        <div v-if="hasOverlays('geotiffOverlays')">
            <overlay-table :overlays="geotiffOverlays" v-on:remove="handleRemove" :volume-id="{{ $volume->id }}" :project-id="{{ $volume->projects->pluck('id')->first() }}" >
            <template v-slot:header>
                <th></th>
                <th>#</th>
                <th>Filename</th>
                <th>Browsing</th>
                <th>Context</th>
                <th>Delete</th>
            </template>
            </overlay-table>
        </div>
        <div v-if="hasOverlays('webmapOverlays')">
            <overlay-table :overlays="webmapOverlays" v-on:remove="handleRemove" :volume-id="{{ $volume->id }}" :project-id="{{ $volume->projects->pluck('id')->first() }}" >
                <template v-slot:title>WebMap Overlays</template>
            </overlay-table>
        </div>
        <ul class="list-group" v-cloak>
            <li class="list-group-item text-muted" v-if="!hasOverlays('geotiffOverlays') && !hasOverlays('webmapOverlays')">This volume has no geo overlays. <a v-if="!editing" href="#" v-on:click.prevent="toggleEditing">Add some.</a></li>
        </ul>
    </div>
</div>
@endif