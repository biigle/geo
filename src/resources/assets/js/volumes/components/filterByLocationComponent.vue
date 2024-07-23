<template>
    <div class="filter-select">
        <geo-map-modal v-if="showModal" :volumeId="volumeId" v-on:on="submit" v-on:close-modal="hideModal" :geoOverlays="browsingOverlays"></geo-map-modal>
        <button type="submit" class="btn btn-default pull-right position" @click="showModal = true">Add rule</button>
    </div>
</template>

<script>
import GeoMapModal from './geoMapModal.vue';
import Api from '../api/geoOverlays';
import {handleErrorResponse} from '../../geo/import';


/**
 * Base component for a filter select element
 *
 * @type {Object}
 */
export default {
    components: {
        geoMapModal: GeoMapModal,
    },
    props: {
        volumeId: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            selectedItem: [],
            showModal: false,
            browsingOverlays: [],
        };
    },
    methods: {
        submit(ids) {
            this.hideModal();
            this.selectedItem = ids;
            this.$emit('select', this.selectedItem);
        },
        hideModal() {
            this.showModal = false;
        }
    },
    created() {
        Api.get({id: this.volumeId, layer_type: 'browsing_layer'})
            .then((response) => {
                if(response.status == 200) {
                    this.browsingOverlays = response.body;
                }
            })
            .catch(handleErrorResponse);
    },
};
</script>

<style scoped>
.filter-select {
    display: block;
    margin-top: -15px;
}

.filter-select > .position {
    position: relative;
    margin-top: 15px;
}
</style>