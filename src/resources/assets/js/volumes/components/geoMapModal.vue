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
            <image-map v-if="images.length && dataLoaded" :images="images" :selectable="true" v-on:select="handleSelectedImages" :overlays="geoOverlays" :overlay-url-template="overlayUrlTemplate" :active-ids="activeIds"></image-map>
        </div>
        <div class="cell cell-edit">
            <div v-if="geoOverlays.length === 0">
                <button class="layer-button" @click="showLayers = !showLayers" title="Show available geo-overlays"><i class="fas fa-layer-group"></i></button>
                <div class="layers" :class="{active: showLayers}">
                    <h4>Geo Overlays</h4>
                    <p class="text-danger">Currently no overlays uploaded / selected.</p>
                    <p class="text-muted">
                        <em>Hint:</em> In the volume-edit section, you can upload a geo-overlay and mark it as browsing-layer to make it available here.
                    </p>
                </div>
            </div>
            <div v-else class="overlays-wrapper">
                <button class="layer-button" @click="showLayers = !showLayers" title="Show available geo-overlays"><i class="fas fa-layer-group"></i></button>
                <div class="layers" :class="{active: showLayers}">
                    <h4>Geo Overlays</h4>
                    <p class="text-muted"><em>Hint:</em> Select an overlay from the list below to show on map.</p>
                    <div v-if="geoOverlays.length !== 0">
                        <p class="help-block">Geo Overlays</p>
                        <div v-for="overlay in geoOverlays" :key="overlay.id">
                            <button :id="overlay.id" :class="{active: activeIds.includes(overlay.id)}" class="list-group-item custom" v-on:click="toggleActive(overlay.id)">
                                <span class="ellipsis" :title="overlay.name" v-text="overlay.name"></span>
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
import {LoaderMixin} from '../import';


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
            dataLoaded: false,
            showLayers: false,
            images: [],
            disabled: true,
            imageIds: [],
            activeIds: [],
            projectId: null,
            geoOverlays: [],
            overlayUrlTemplate: '',
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
            if(this.activeIds.includes(id)) {
                let index = this.activeIds.indexOf(id);
                this.activeIds.splice(index, 1);
            } else {
                this.activeIds.push(id);
            }
        },
    },
    async created() {
        // show the modal upon trigger-event
        this.startLoading();
        this.show = true;
        // get all image + coordinate information from volume-images
        await CoordApi.get({id: this.volumeId})
            .then(response => this.images = response.body, this.handleErrorResponse)
            .finally(this.finishLoading);

        // get the overlayUrlTemplate string
        this.overlayUrlTemplate = await fetch(biigle.$require('geo.overlayUrlTemplate')).then((response) => {
            return response.json().then((body) => {
                return body.url;
            });
        });

        this.projectId = biigle.$require('geo.projectId');
        // provide overlays array (only those where browsing_overlay = true)
        this.geoOverlays = biigle.$require('geo.geoOverlays');
        // initially fill activeIds with selected overlays
        this.activeIds = this.geoOverlays.map(x => x.id);
        
        // prevent imageMap component from rendering before data is fetched
        this.dataLoaded = true;
    }
}
</script>

<style scoped>
    .image-map {
        height: 450px;
    }

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
        font-size: 1.5em;
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

