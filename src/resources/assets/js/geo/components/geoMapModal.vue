<template>
    <modal 
        v-model="show"
        title="Map Filter" 
        ok-text="Add rule"
        cancel-text="Cancel"
        ok-type="default"
        cancel-type="default"
        size="lg"
        v-on:hide="callback"
      >
        <p v-html="text"></p>
        <div class="map-container">
            <div class="sidebar-container__content">
                <image-map v-if="images.length" :images="images" :preselected="selectedImages" :selectable="true" v-on:select="handleSelectedImages"></image-map>
            </div>
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
        text: {
            type: String,
            required: true,
        },
        showModal: {
            type: Boolean,
            required: true,
        },
        volumeId: {
            type: Number,
            required: true,
        }
    },
    data() {
        return {
            show: false,
            images: [],
        }
    },
    computed: {
        selectedImages() {
            // These will be the selected images of previous sessions.
            // Vue will not be able to reactively update this property.
            return JSON.parse(sessionStorage.getItem(this.key)) || [];
        },
        key() {
            return 'biigle.geo.filter.imageSequence.' + this.volumeId;
        },
    },
    methods: {
        // trigger addRule() on parent
        callback(msg) {
            if (msg == 'ok') {
                this.$emit('on', this.key);
            } else {
                this.$emit("close-modal");
            }
        },
        handleSelectedImages(ids) {
            if (ids.length > 0) {
                sessionStorage.setItem(this.key, JSON.stringify(ids));
            } else {
                sessionStorage.removeItem(this.key);
            }
        },
    },
    created() {
        // show the modal upon trigger-event
        this.startLoading();
        this.show = true;
        // get all image + coordinate information from volume-images
        CoordApi.get({id: this.volumeId}, {})
            .then(
                (response) => {
                    this.images = response.body;
                    this.finishLoading();
            },
            (response) => {
                return this.handleErrorResponse(response);
            });
    },
}
</script>