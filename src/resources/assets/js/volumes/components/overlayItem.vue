<template>
    <tr>
        <td class="handle start">
            <i class="fas fa-grip-lines"></i>
        </td>
        <td scope="row" class="start">
            <span class="text-muted">#<span>{{ index + 1 }}</span></span>
        </td>
        <td class="start">
            <span class="ellipsis" :title="overlay.name" v-text="truncateString(overlay.name)"></span>
        </td>
        <td>
            <!-- browsing-layer -->
            <span class="power-toggle">
                <button class="btn btn-default btn-sm" :class="{'active btn-info': browsingLayer}" v-on:click="toggleButton('browsingLayer')" title="Add overlay to geo-selection filter">
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
        toggleButton(dataKey) {
            this.update(dataKey)
                .then((response) => {
                    if(response.status == 200) {
                        this[dataKey] = !this[dataKey];
                    }
                })
                .catch(handleErrorResponse);
        },
        update(dataKey) {
            return Api.updateGeoOverlay({id: this.volumeId, geo_overlay_id: this.overlay.id}, {
                layer_type: dataKey,
                use_layer: !this[dataKey],
                });
        },
        // checks if string is too long and returns truncated version
        truncateString(str) {
            const n = 22;
            const ext = str.substring(str.lastIndexOf("."));

            if(str.length > n) {
                // check if the file extension exists (ext != the full string)
                if(ext.length < str.length) {
                    return str.slice(0, n-1) + '...' + ext;
                }
                return str.slice(0, n-1) + '...';
            }
            return str;
        }
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
    white-space: nowrap;
    min-width: 200px;
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

/* center the delete button */
.close {
    float: none;
}
</style>