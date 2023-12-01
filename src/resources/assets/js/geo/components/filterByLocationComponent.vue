<template>
    <div class="filter-select">
        <geo-map-modal v-if="showModal" :showModal="showModal" :text="text" :volumeId="volumeId" v-on:on="submit" v-on:close-modal="hideModal"></geo-map-modal>
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
            selectedItem: null,
            showModal: false,
        };
    },
    methods: {
        submit(key) {
            this.hideModal();
            // pass the array of selected IDs as JSON-string in order to work properly in the hasRule()-check later on.
            // hasRule() function checks whether rule-attributes diverge between existing and new rules
            // e.g. newRule.data === oldRule.data (does not assert equality for array- or object-comparison)
            let ids = JSON.parse(sessionStorage.getItem(key)) || [];
            this.selectedItem = JSON.stringify(ids);
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

.filter-select > .position {
    position: relative;
    margin-top: 15px;
}
</style>