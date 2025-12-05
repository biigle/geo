<template>
    <tr>
        <td class="handle start">
            <i class="fas fa-grip-lines"></i>
        </td>
        <td scope="row" class="start">
            <span class="text-muted">#<span>{{ index + 1 }}</span></span>
        </td>
        <td class="start">
            <span class="truncate" :title="overlay.name" v-text="overlay.name"></span>
        </td>
        <td>
            <span class="power-toggle">
                <button class="btn btn-default btn-sm" :class="{'active btn-info': browsingLayer}" v-on:click="updateShowLayer" title="Add overlay to geo-selection filter">
                    <i class="fa fa-fw fa-power-off"></i>
                </button>
            </span>
        </td>
        <td>
            <button type="button" class="close" :title="title" v-on:click="remove" v-once><span aria-hidden="true">&times;</span></button>
        </td>
    </tr>
</template>

<script>
import Api from '../api/geoOverlays.js';
import {handleErrorResponse} from '../../geo/import.js';


export default {
    data() {
        return {
            browsingLayer: true,
        }
    },
    props: {
        overlay: {type: Object, required: true},
        volumeId: {type: Number, required: true,},
        index: {type: Number, required: true},
    },
    computed: {
        title() {
            return 'Delete overlay ' + this.overlay.name;
        },
    },
    methods: {
        remove() {
            if (confirm(`Are you sure you want to delete the overlay ${this.overlay.name}?`)) {
                this.$emit('remove', this.overlay);
            }
        },
        // handle update of contextLayer & browsingLayer values in overlay
        updateShowLayer() {
            Api.updateGeoOverlay({ id: this.volumeId, geo_overlay_id: this.overlay.id }, { browsing_layer: !this.browsingLayer })
                .then((res) => {
                    let overlay = res.data;
                    this.browsingLayer = overlay.browsing_layer;
                }).catch(handleErrorResponse);
        },
    },
    mounted() {
        // initially set the two values 
        this.browsingLayer = this.overlay.browsing_layer;
    }
};
</script>
<style scoped>
/* when row-handle is selected for dragging, highlight the entire row */
tr:has(.handle:active) {
    background-color: #7c7c7c;
}

/* define the different column widths individually */
td:nth-child(1) {
    width: 30px;
}
td:nth-child(2) {
    width: 35px;
}
td:nth-child(3) {
    width: 200px;
}
td:nth-child(4) {
    min-width: 90px;
}
td:nth-child(5) {
    min-width: 80px;
}
td:nth-child(6) {
    min-width: 65px;
}

/* align all row entries in center except the ones marked with "start" */
td:not(.start) {
    text-align: center;
}

.truncate {
    text-overflow: ellipsis;
    display: inline-block;
    white-space: nowrap;
    overflow: hidden;
    width: 200px;
}

/* center the delete button */
.close {
    float: none;
}
</style>