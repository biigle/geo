<template>
    <div class="table-responsive">
        <table v-if="overlays.length !== 0" class="table table-sm" v-cloak>
            <thead>
                <tr>
                    <th></th>
                    <th>#</th>
                    <th>Filename</th>
                    <th>Browsing</th>
                    <th>Context</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <draggable v-model="sortedOverlays" tag="tbody" handle=".handle">
                <tr is="overlay-item" v-for="(overlay, idx) in sortedOverlays" :key="overlay.id" :class="{'list-group-item-success': overlay.isNew}" :index="idx" :overlay="overlay" :volume-id="volumeId" v-on:remove="$emit('remove', overlay);">
                </tr>
            </draggable>
        </table>
    </div>
</template>

<script>
import OverlayItem from './overlayItem';
import draggable from 'vuedraggable';


export default {
    data() {
        return {
            sortedOverlays: [],
            dataLoaded: false,
        };
    },
    props: {
        overlays: {
            type: Array,
            required: true,
        },
        volumeId: {
            type: Number,
            required: true,
        },
        projectId: {
            type: Number,
            required: true,
        }
    },
    methods: {
    },
    watch: {
        overlays(overlays) {
            // create simplified arrays of overlay-ids
            let overlaysId = overlays.map(x => x.id);
            let sortedOverlaysId = this.sortedOverlays.map(x => x.id);

            // in case an overlay was deleted
            if(overlays.length < this.sortedOverlays.length) {
                // find the id of the removed overlay 
                let removedId = sortedOverlaysId.filter(x => !overlaysId.includes(x));
                // remove the deleted overlay from sortedOverlays-array
                let removedIdIndex = this.sortedOverlays.findIndex(x => x.id === removedId[0]);
                this.sortedOverlays.splice(removedIdIndex, 1);
            } else { 
                // in case an overlay was added, find its Id and add the overlay to sortedOverlays-array 
                let addedId = overlaysId.filter(x => !sortedOverlaysId.includes(x));
                this.sortedOverlays.unshift(overlays.find(x => x.id === addedId[0]));
            }
        },
        sortedOverlays(sortedArray) {
            if(this.dataLoaded) {
                let indexArray = sortedArray.map(x => x.id);
                // save the new overlay-order in localStorage variable
                window.localStorage.setItem(`geotiff-upload-order-${this.projectId}-${this.volumeId}`, JSON.stringify(indexArray));
            }
        }
    },
    mounted() {
        // initially retrieve the array of ordered overlay-ids 
        let overlayOrder = JSON.parse(window.localStorage.getItem(`geotiff-upload-order-${this.projectId}-${this.volumeId}`));
        if(overlayOrder) {
            // add the overlays according to the specified order in overlayOrder-array
            for(let id of overlayOrder) {
                this.sortedOverlays.push(this.overlays.find(x => x.id === id));
            }
        } else { // default case
            this.sortedOverlays = JSON.parse(JSON.stringify(this.overlays));
        }
        this.$nextTick(() => { //with this skip the first change of sortedOverlays
            this.dataLoaded = true;
        })
    },
    components: {
        overlayItem: OverlayItem,
        draggable,
    }
};
</script>

<style scoped>

.table-responsive {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
    max-height: 300px;
    overflow-x: scroll;
    overflow-y: scroll;
}

th {
    white-space: normal;
    word-wrap: break-word;
}
</style>