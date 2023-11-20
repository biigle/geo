<template>
    <div class="filter-select">
        <geo-map-modal id="gmm" :text="text" :trigger="trigger" :items="items" v-on:on="submit"></geo-map-modal>
        <button type="submit" class="btn btn-default pull-right" @click="trigger = !trigger">Add rule</button>
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
            items: [],
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
        gotItems(response) {
            this.items = response.body;
            console.log("filterByLoc items: ", this.items);
        },
        // retrieveFiles(response) {
        //     this.images = Object.keys(response.data);
        // },
        submit() {
            this.$emit('select', this.selectedItem);
        },
    }
};
</script>
