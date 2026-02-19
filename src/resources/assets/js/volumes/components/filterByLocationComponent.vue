<template>
    <div class="filter-select">
        <geo-map-modal v-if="showModal" :volumeId="volumeId" v-on:on="submit" v-on:close-modal="hideModal"></geo-map-modal>
        <button type="submit" class="btn btn-default pull-right position" @click="showModal = true">Add rule</button>
    </div>
</template>

<script>
import GeoMapModal from './geoMapModal.vue';

/**
 * Base component for a filter select element
 *
 * @type {Object}
 */
export default {
    emits: ['select'],
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
    }
};
</script>

<style scoped>
.filter-select {
    display: block;
    margin-top: -15px;
}

.filter-select>.position {
    position: relative;
    margin-top: 15px;
}
</style>
