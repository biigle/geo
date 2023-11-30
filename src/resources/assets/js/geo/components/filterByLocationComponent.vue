<template>
    <div class="filter-select">
        <geo-map-modal v-if="showModal" :showModal="showModal" :text="text" :volumeId="volumeId" v-on:on="submit" v-on:close-modal="hideModal"></geo-map-modal>
        <button type="submit" class="btn btn-default pull-right position" @click="showModal = true">Add rule</button>
    </div>
</template>

<script>
import GeoMapModal from './geoMapModal.vue';

// create custom event to update rule upon changes in sessionStorage
const customEvent = new Event('storageUpdate', {
    bubbles: true,
    cancelable: true,
    composed: false
  });

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
        submit() {
            this.hideModal();
            // always trigger rule-refresh in geoFIlter.js (in case the rule has already been applied)
            // not regarding whether sessionStorage-data has been changed or not 
            window.dispatchEvent(customEvent);
            // selectedItem is always null, so geoFilter-rule can only be added once
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