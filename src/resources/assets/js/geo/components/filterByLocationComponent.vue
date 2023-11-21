<template>
    <div class="filter-select">
        <geo-map-modal id="gmm" :text="text" :trigger="trigger" :volumeId="volumeId" v-on:on="submit"></geo-map-modal>
        <button type="submit" class="btn btn-default pull-right position" @click="trigger = !trigger">Add rule</button>
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
            trigger: false,
        };
    },
    computed: {
        value() {
            return this.selectedItem ? this.selectedItem.name : '';
        },
    },
    methods: {
        select(item) {
            this.selectedItem = item;
        },
        submit() {
            this.$emit('select', this.selectedItem);
        },
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