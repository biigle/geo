<template>
    <div class="filter-select">
        <geo-map-modal id="gmm" :text="text" :trigger="trigger" :images="images" v-on:on="submit"></geo-map-modal>
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
            images: [],
            items: [],
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
        // retrieveFiles(response) {
        //     this.images = Object.keys(response.data);
        // },
        gotItems(response) {
            this.items = response.data;
        },
        submit() {
            this.$emit('select', this.selectedItem);
        },
    },
    watch: {
        items(newVal) {
            console.log("items: ", newVal);
        }
    }
};
</script>
