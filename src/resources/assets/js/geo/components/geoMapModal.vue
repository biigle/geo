<template>
    <modal 
        v-model="show"
        title="Map Filter"
        size="lg"
        :backdrop="false"
      >
        <div class="sidebar-container__content">
            <image-map v-if="images.length" :images="images" :selectable="true" v-on:select="handleSelectedImages"></image-map>
        </div>
        <p class="text-muted">
            <em>Hint:</em> Select image locations on the volume map by drawing an encompassing rectangle. To do this, press and hold <kbd>Ctrl</kbd> as well as the left mouse button and move the cursor on the map.
        </p>
        <div slot="footer">
            <button class="btn btn-default" @click="callback(false)">Cancel</button>
            <button class="btn btn-default" @click="callback(true)" :disabled="disabled">Add rule</button>
      </div>
    </modal>
</template>

<script>
import Modal from 'uiv/dist/Modal';
import ImageMap from './imageMap';
import CoordApi from '../api/volumeImageWithCoord';
import {LoaderMixin} from '../../volumes/import';

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
        // trigger addRule() on parent
        callback(msg) {
            if (msg) {
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

<style lang="scss">
// import css styles for ol-dragbox
@import '/assets/styles/main.css';

p {
    padding-top: 10px;
}
</style>