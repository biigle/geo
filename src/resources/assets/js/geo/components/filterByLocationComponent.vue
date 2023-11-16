<template>
    <div class="filter-select">
        <!-- <typeahead :items="items" :value="value" :placeholder="placeholder" @select="select" :more-info="typeaheadMoreInfo"></typeahead> -->
        <geo-map-modal id="gmm" :text="text" :trigger="trigger" :images="images" v-on:on="submit"></geo-map-modal>
        <button type="submit" class="btn btn-default pull-right" @click="trigger = !trigger">Add rule</button>
    </div>
</template>

<script>
import GeoMapModal from './Modal.vue';

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
            images: [],
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
        retrieveFiles(response) {
            this.images = Object.keys(response.data);
            console.log("images: ", this.images);
        },
        submit() {
            this.$emit('select', this.selectedItem);
        },
    },
};
</script>
