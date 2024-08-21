<template>
    <modal 
        v-model="show"
        title="Map Filter"
        size="lg"
        :backdrop="false"
        @hide="callback"
      >
    <div class="content">
        <div class="cell cell-map">
            <image-map v-if="images.length" :images="images" :selectable="true" v-on:select="handleSelectedImages" :overlays="overlays" :web-map-overlays="webmapOverlaysSorted"></image-map>
        </div>
        <div class="cell cell-edit">
            <div v-if="geotiffOverlays.length === 0 && webmapOverlaysSorted.length === 0">
                <button class="layer-button" @click="showLayers = !showLayers" title="Show available geo-overlays"><i class="fas fa-layer-group" style="font-size: 1.5em;"></i></button>
                <div class="layers" :class="{active: showLayers}">
                    <h4>Geo Overlays</h4>
                    <p class="text-danger">Currently no overlays uploaded / selected.</p>
                    <p class="text-muted">
                        <em>Hint:</em> In the volume-edit section, you can upload a geo-overlay and mark it as browsing-layer to make it available here.
                    </p>
                </div>
            </div>
            <div v-else class="overlays-wrapper">
                <button class="layer-button" @click="showLayers = !showLayers" title="Show available geo-overlays"><i class="fas fa-layer-group" style="font-size: 1.5em;"></i></button>
                <div class="layers" :class="{active: showLayers}">
                    <h4>Geo Overlays</h4>
                    <p class="text-muted"><em>Hint:</em> Select an overlay from the list below to show on map.</p>
                    <div v-if="geotiffOverlays.length !== 0">
                        <p class="help-block">Geotiff Overlays</p>
                        <div v-for="tifOverlay in geotiffOverlays" :key="tifOverlay.id">
                            <button :id="tifOverlay.id" :class="{active: activeTifIds.includes(tifOverlay.id)}" class="list-group-item custom" v-on:click="toggleActive('activeTifIds', tifOverlay.id)">
                                <span class="ellipsis" :title="tifOverlay.name" v-text="tifOverlay.name"></span>
                            </button>
                        </div> 
                    </div>
                    <div v-if="webmapOverlaysSorted.length !== 0">
                        <p class="help-block">WebMap Overlays</p>
                        <div v-for="wmsOverlay in webmapOverlaysSorted" :key="wmsOverlay.id">
                            <button :id="wmsOverlay.id" :class="{active: activeWmsIds.includes(wmsOverlay.id)}" class="list-group-item custom" v-on:click="toggleActive('activeWmsIds', wmsOverlay.id)">
                                <span class="ellipsis" :title="wmsOverlay.name" v-text="wmsOverlay.name"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="cell cell-content">
            <p class="text-muted">
                <em>Hint:</em> Select image locations on the volume map by drawing an encompassing rectangle. To do this, press and hold <kbd>Ctrl</kbd> as well as the left mouse button and move the cursor on the map.
            </p>
        </div>
    </div>
        <div slot="footer">
            <button class="btn btn-default" @click="callback('dismiss')" >Cancel</button>
            <button class="btn btn-default" @click="callback('ok')" :disabled="disabled">Add rule</button>
        </div>
    </modal>
</template>

<script>
import Modal from 'uiv/dist/Modal';
import ImageMap from '../../geo/components/imageMap';
import CoordApi from '../api/volumeImageWithCoord';
import GeoApi from '../api/geoOverlays';
import {LoaderMixin} from '../import';
import TileLayer from 'ol/layer/Tile';
import ZoomifySource from 'ol/source/Zoomify';


export default {
    mixins: [LoaderMixin],
    components: {
        modal: Modal,
        imageMap: ImageMap,
    },
    props: {
        volumeId: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            show: false,
            showLayers: false,
            images: [],
            disabled: true,
            imageIds: [],
            activeTifIds: [],
            activeWmsIds: [],
            overlayUrlTemplate: '',
            overlays: [],
            projectId: null,
            geotiffOverlays: [],
            webmapOverlays: [],
            webmapOverlaysSorted: [],
            geotiffOrder: [],
            webmapOrder: [],
        }
    },
    methods: {
        callback(msg) {
            if (msg === "ok") {
                // trigger addRule() on parent
                this.$emit('on', this.imageIds);
            } else {
                this.$emit("close-modal");
            }
        },
        handleSelectedImages(ids) {
            if (ids.length > 0) {
                this.imageIds = [...ids.sort()];
                this.disabled = false;
            } else {
                this.imageIds = [];
                this.disabled = true;
            }
        },
        // varKey is either 'activeTifIds' or 'activeWmsIds' Array
        toggleActive(varKey, id) {
            if(this[varKey].includes(id)) {
                let index = this[varKey].indexOf(id);
                this[varKey].splice(index, 1);
            } else {
                this[varKey].push(id);
            }
        },
        // takes array of overlays as input and returns them as ol-tileLayers
        createOverlayTile(overlays) {
            return overlays.map((overlay) => {
                return new TileLayer({
                    source: new ZoomifySource({
                            url: this.overlayUrlTemplate.replaceAll(':id', overlay.id),
                            size: [overlay.attrs.width, overlay.attrs.height],
                            extent: [
                                0,
                                0,
                                overlay.attrs.width,
                                overlay.attrs.height
                                // overlay.top_left_lng,
                                // overlay.bottom_right_lat,
                                // overlay.bottom_right_lng,
                                // overlay.top_left_lat,
                            ],
                            transition: 100,
                            zDirection: -1
                    })
                });
            });
    }
    },
    watch: {
        geotiffOverlays(geotiffOverlays) {
            // adhere to the specific overlay order, if it has been defined in the volume settings
            if(this.geotiffOrder) {
                for(let id of this.geotiffOrder) {
                    // call createOverlayTile-method with one overlay at a time
                    let idx = geotiffOverlays.findIndex(x => x.id === id);
                    if(idx !== -1) {
                        // call creatOverlayTile by wrapping the single overlay in an Array.
                        // This way the method works for both cases (whether order exists or not)
                        let overlayTileArr = this.createOverlayTile([geotiffOverlays[idx]]);
                        // add newly created overlayTile to overlays-array
                        this.overlays.push(...overlayTileArr);
                    }
                }
            } else { // default case
                // call createOverlayTile-method with complete overlays-array, as order does not matter
                this.overlays = this.createOverlayTile(geotiffOverlays)
            }
        },
        webmapOverlays(webmapOverlays) {
            // adhere to the specific overlay order, if it has been defined in the volume settings
            if(this.webmapOrder) {
                // Sort the original webmapOverlays array according to the order of ids
                this.webmapOverlaysSorted = webmapOverlays.toSorted((a, b) => {
                    return this.webmapOrder.indexOf(a.id) - this.webmapOrder.indexOf(b.id)
                });
            } else {
                this.webmapOverlaysSorted = [...webmapOverlays];
            }
        }
    },
    async created() {
        // show the modal upon trigger-event
        this.startLoading();
        this.show = true;
        // get all image + coordinate information from volume-images
        CoordApi.get({id: this.volumeId})
            .then(response => this.images = response.body, this.handleErrorResponse)
            .finally(this.finishLoading);

            // provide overlay-url template string
        await GeoApi.getGeoTiffOverlayUrlTemplate({id: this.volumeId})
            .then((response) => {
                this.overlayUrlTemplate = response.body;
            });

        this.projectId = biigle.$require('geo.projectId');
        // retrieve the array of ordered overlay-ids
        this.geotiffOrder = JSON.parse(window.localStorage.getItem(`geotiff-upload-order-${this.projectId}-${this.volumeId}`));
        this.webmapOrder = JSON.parse(window.localStorage.getItem(`webmap-upload-order-${this.projectId}-${this.volumeId}`));
        // provide overlays array (only those where browsing_overlay = true)
        this.geotiffOverlays = biigle.$require('geo.geotiffOverlays');
        this.webmapOverlays = biigle.$require('geo.webmapOverlays');
        // initially fill activeIds with selected overlays
        this.activeTifIds = this.geotiffOverlays.map(x => x.id);
        this.activeWmsIds = this.webmapOverlays.map(x => x.id); 
    }
}
</script>

<style scoped>
    p {
        padding-top: 10px;
    }

    /* Grid settings */
    .content {
        display: grid;
        grid-template-columns: auto minmax(30px, auto);
        grid-auto-rows: max-content;
        grid-gap: 1rem;
        padding: 1rem;
        box-sizing: border-box;
    }

    .cell {
        border-radius: 4px;
        background-color: none;
    }

    .cell-map {
        grid-column: 1;
        grid-row-start: 1;
        grid-row-end: 2;
    }

    .cell-edit {
        grid-column: 2;
        grid-row: 1;
        min-width: min(min-content, 200px);
        max-width: 200px;
    }

    .cell-content {
        grid-row: 2;
        grid-column-start: 1;
        grid-column-end: 2;
    }

    /* layer-items settings */
    .list-group-item.active {
        background-color: #353535;
        color: #ffffff;
    }

    .layer-button {
        position: absolute;
        top: 20px;
        right: 20px;
        display: block;
        background-color: var(--body-bg);
        text-decoration: none;
        border: none;
        color: #aaaaaa;
        padding: 15px;
        z-index: 9;
    }

    .layer-button:active {
        color: white;
        transform: scale(.9);
    }

    .overlays-wrapper {
        max-height: 320px;
        overflow-y: scroll;
    }

    /* modify the grid cells upon active layers */
    .layers {
        display: none;
    }

    .layers.active {
        display: block;
    }

    /* change cell-map to occupy only one col */
    /* .layers.active + .cell-map {
        grid-column: 1;
        grid-row-start: 1;
        grid-row-end: 2;
    }

    .layers.active + .cell-edit {
        display: block;
        grid-column: 2;
        grid-row: 1;
    } */

</style>