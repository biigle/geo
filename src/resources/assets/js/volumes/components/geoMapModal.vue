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
            <image-map v-if="images.length" :images="images" :selectable="true" v-on:select="handleSelectedImages" :overlays="overlays"></image-map>
        </div>
        <div class="cell cell-edit">
            <h4>Geo Overlays</h4>
            <p>Select an overlay from the list below to show on map.</p>
            <div v-for="overlay in browsingOverlays" :key="overlay.id">
                <button :id="overlay.id" :class="{active: activeLayerId === overlay.id}" class="list-group-item custom" v-on:click="toggleActive(overlay.id)">
                    <span class="ellipsis" v-text="overlay.name"></span>
                </button>
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
            images: [],
            disabled: true,
            imageIds: [],
            activeLayerId: null,
            overlay: null,
            overlayUrlTemplate: '',
            overlays: [],
            browsingOverlays: [],
            projectId: null,
            overlayOrder: [],
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
        toggleActive(id) {
            if(id === this.activeLayerId) {
                this.activeLayerId = null;
            } else {
                this.activeLayerId = id;
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
                            ]
                    }),
                    name: 'overlayTile-' + overlay.id
                });
            });
        }
    },
    watch: {
        // select the geo overlay based on currently active id
        activeLayerId(id) {
            if(id === null) {
                this.overlay = null;
            } else {
                this.overlay = this.browsingOverlays.find(x => x.id === id);
            }
        },
        // 
        browsingOverlays(browsingOverlays) {
            // adhere to the specific overlay order, if it has been defined in the volume settings
            if(this.overlayOrder) {
                for(let id of this.overlayOrder) {
                    // call createOverlayTile-method with one overlay at a time
                    let singleBrowsingOverlay = browsingOverlays.find(x => x.id === id);
                    // handle case of missing id (not all overlays have to be marked as browsingOverlays)  
                    if(typeof singleBrowsingOverlay !== 'undefined') {
                        let overlayTileArr = this.createOverlayTile([singleBrowsingOverlay]);
                        // add newly created overlayTile to overlays-array
                        this.overlays.push(...overlayTileArr);
                    }
                }
            } else { // default case
                // call createOverlayTile-method with complete overlays-array, as order does not matter
                this.overlays = this.createOverlayTile(browsingOverlays)
            }
        }
    },
    created() {
        // show the modal upon trigger-event
        this.startLoading();
        this.show = true;
        // get all image + coordinate information from volume-images
        CoordApi.get({id: this.volumeId})
            .then(response => this.images = response.body, this.handleErrorResponse)
            .finally(this.finishLoading);

        // provide overlay-url template string
        GeoApi.getGeoTiffOverlayUrlTemplate({id: this.volumeId})
            .then((response) => {
                this.overlayUrlTemplate = response.body;
        });

        this.projectId = biigle.$require('geo.projectId');
        // retrieve the array of ordered overlay-ids
        this.overlayOrder = JSON.parse(window.localStorage.getItem(`geotiff-upload-order-${this.projectId}-${this.volumeId}`));
        // provide overlays array (only those where browsing_overlay = true)
        this.browsingOverlays = biigle.$require('geo.browsingOverlays');
    },
}
</script>

<style scoped>
    p {
        padding-top: 10px;
    }

    .content {
        display: grid;
        grid-template-columns: auto 200px;
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
    }

    .cell-content {
        grid-row: 2;
        grid-column-start: 1;
        grid-column-end: 2;
    }

    .list-group-item.active {
        background-color: rgba(255, 166, 0, 0.5);
    }
</style>