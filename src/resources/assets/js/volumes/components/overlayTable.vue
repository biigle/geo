<template>
    <div class="table-responsive">
        <table v-if="overlays.length !== 0" class="table table-sm" v-cloak>
            <caption><b><slot name="title"></slot></b></caption>
            <thead>
                <tr>
                    <slot name="header">
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </slot>
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
import GeoApi from '../api/geoOverlays';
import {handleErrorResponse} from '../../geo/import';



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
        },
        overlayType: {
            type: String,
            required: false,
            default: null
        }
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
                // save the new overlay-order in geo_overlays table
                for(let [idx, overlay] of sortedArray.entries()) {
                    GeoApi.updateGeoOverlay({id: this.volumeId, geo_overlay_id: overlay.id}, {
                        layerIndex: idx,
                    })
                    .catch(handleErrorResponse);
                }
            }
        }
    },
    mounted() {
        this.sortedOverlays = JSON.parse(JSON.stringify(this.overlays));
        this.sortedOverlays.sort((a, b) => a.layer_index - b.layer_index);

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

table {
    table-layout: auto;
}

th {
    white-space: normal;
    word-wrap: break-word;
}

caption {
    text-align: center;
}
</style>