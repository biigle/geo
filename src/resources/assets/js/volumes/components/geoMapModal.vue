<template>
    <modal 
        v-model="show"
        title="Map Filter"
        size="lg"
        :backdrop="false"
        @hide="callback"
      >
        <image-map v-if="images.length" :images="images" :selectable="true" v-on:select="handleSelectedImages"></image-map>
        <p class="text-muted">
            <em>Hint:</em> Select image locations on the volume map by drawing an encompassing rectangle. To do this, press and hold <kbd>Ctrl</kbd> as well as the left mouse button and move the cursor on the map.
        </p>
        <template #footer>
            <div>
                <button class="btn btn-default" @click="callback('dismiss')" >Cancel</button>
                <button class="btn btn-default" @click="callback('ok')" :disabled="disabled || null">Add rule</button>
            </div>
        </template>
    </modal>
</template>

<script>
import CoordApi from '../api/volumeImageWithCoord.js';
import ImageMap from '../../geo/components/imageMap.vue';
import {LoaderMixin} from '../import.js';
import {Modal} from '../import.js';

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
        }
    },
    data() {
        return {
            show: false,
            images: [],
            disabled: true,
            imageIds: [],
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
    .image-map {
        height: 450px;
    }

    p {
        padding-top: 10px;
    }
</style>
