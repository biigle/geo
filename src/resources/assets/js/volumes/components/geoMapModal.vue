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
            <image-map v-if="images.length" :images="images" :selectable="true" v-on:select="handleSelectedImages" :overlay="overlay"></image-map>
        </div>
        <div class="cell cell-edit">
            <h4>Geo Overlays</h4>
            <p>Select an overlay from the list below to show on map.</p>
            <div v-for="overlay in geoOverlays" :key="overlay.id">
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
        geoOverlays: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            show: false,
            images: [],
            disabled: true,
            imageIds: [],
            activeLayerId: null,
            overlay: null,
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
        }
    },
    watch: {
        // select the geo overlay based on currently active id
        activeLayerId(id) {
            if(id === null) {
                this.overlay = null;
            } else {
                this.overlay = this.geoOverlays.find(x => x.id === id);
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