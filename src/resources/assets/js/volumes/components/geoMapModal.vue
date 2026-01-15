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
            <image-map v-if="showOverlay" :images="images" :selectable="true" v-on:select="handleSelectedImages" :overlays="geoOverlays" :overlay-url-template="overlayUrlTemplate" :last-action="lastAction" :hide-ids="hideIds"></image-map>
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
                            <button :id="overlay.id" :class="{active: activeIds.includes(overlay.id)}" class="list-group-item custom" v-on:click="toggleActive(overlay.id)" :disabled="isDisabled(overlay.id)">
                                <span class="ellipsis" :title="overlay.name" v-text="overlay.name"></span>
                            </button>
                        </div> 
                    </div>
                </div>
            </div>
        </div>
        <div class="cell cell-content">
            <p class="text-muted">
                <em>Hint:</em> Select image locations on the volume map by drawing an encompassing rectangle. To do this, press and hold <kbd>Ctrl</kbd> (<kbd>Cmd &#8984;</kbd> on Mac) as well as the left mouse button and move the cursor on the map.
            </p>
        </div>
    </div>
    </modal>
</template>

<script>
import CoordApi from '../api/volumeImageWithCoord.js';
import VolumeApi from '../api/geoOverlays.js';
import ImageMap from '../../geo/components/imageMap.vue';
import {LoaderMixin, Events, Modal} from '../import.js';


export default {
    emits: [
        'on',
        'close-modal',
    ],
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
            activeIds: [],
            geoOverlays: [],
            overlayUrlTemplate: '',
            lastAction: [],
            disabledIds: [],
            hideIds: new Set(),
        }
    },
    computed: {
        showOverlay() {
            return this.images.length && !this.loading;
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
            if (this.activeIds.includes(id)) {
                let index = this.activeIds.indexOf(id);
                this.activeIds.splice(index, 1);
            } else {
                this.activeIds.push(id);
            }
            this.lastAction = ['selectedOverlay', id];
        },
        changeLastAction() {
            if (this.lastAction[0] === 'filter-map-changed') {
                return;
            }

            let lastOverlayId = this.lastAction[1];
            this.lastAction = ['filter-map-changed', lastOverlayId];
        },
        addToDisabledIds(id) {
            this.disabledIds.push(id);
        },
        isDisabled(id) {
            return this.disabledIds.includes(id) || this.hideIds.has(id);
        }
    },
    created() {
        Events.on('disable-overlay-btn', (id) => this.addToDisabledIds(id));
        Events.on('filter-map-action', () => this.changeLastAction());
        // show the modal upon trigger-event
        this.startLoading();
        this.show = true;
        // get all image + coordinate information from volume-images
        CoordApi.get({id: this.volumeId})
            .then(response => this.images = response.body, this.handleErrorResponse);


        VolumeApi.getOverlays({ 'id': this.volumeId })
            .then(response => {
                this.overlayUrlTemplate = response.body.urlTemplate;
                this.geoOverlays = response.body.geoOverlays;
                this.hideIds = new Set(this.geoOverlays.filter(o => !o.browsing_layer).map(o => o.id));

            }, this.handleErrorResponse)
            .finally(this.finishLoading);

        // initially fill activeIds with selected overlays
        this.activeIds = this.geoOverlays.map(x => x.id);
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

    .list-group-item:hover:disabled {
        background-color: transparent;
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

    .custom {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        gap: 10px;
    }

    .custom > .ellipsis {
        order: 1;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
}

</style>

