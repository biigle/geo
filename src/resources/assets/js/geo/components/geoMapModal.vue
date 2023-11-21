<template>
    <modal 
        v-model="showModal" 
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
import LabelApi from '../api/volumeImageWithLabel';
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
        trigger: {
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
            showModal: false,
            images: [],
        }
    },
    computed: {
        selectedImages() {
            // These will be the selected images of previous sessions.
            // Vue will not be able to reactively update this property.
            return JSON.parse(localStorage.getItem(this.key)) || [];
        },
        key() {
            return 'biigle.geo.imageSequence.' + this.volumeId;
        },
    },
    methods: {
        // trigger addRule() on parent
        callback(msg) {
            if (msg == 'ok') {
                this.$emit('on');
            } else {
                return null;
            }
        },
        handleSelectedImages(ids) {
            if (ids.length > 0) {
                localStorage.setItem(this.key, JSON.stringify(ids));
            } else {
                localStorage.removeItem(this.key);
            }
        },
        getImageFilterApi(id) {
            return LabelApi.get({vid: this.volumeId, lid: id}, {});
        },
    },
    watch: {
        // show the modal upon trigger-event
        trigger: function() {
            this.startLoading();
            this.showModal = true;
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
    },
}
</script>

<!-- <style>
.map-container {
    color: rgba(255, 0, 0, 0.507);
    height: 100px;
    width: 100%;
    display: flex;
    flex-direction: row;
    position: absolute;
}
</style> -->