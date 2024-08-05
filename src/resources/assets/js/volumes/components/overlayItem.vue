<template>
    <tr>
        <td class="handle start">
            <i class="fas fa-grip-lines"></i>
        </td>
        <td scope="row" class="start">
            <span class="text-muted">#<span>{{ index + 1 }}</span></span>
        </td>
        <td class="start">
            <span class="ellipsis" v-text="truncateString(overlay.name)"></span>
        </td>
        <td>
            <!-- browsing-layer -->
            <button type="button" class="toggle-btn" :class="{active: browsingLayer}" v-on:click="toggleButton('browsingLayer')"><span class="fa fa-circle" aria-hidden="true"></span></button>
        </td>
        <td>
            <!-- context layer -->
            <button type="button" class="toggle-btn" :class="{active: contextLayer}" v-on:click="toggleButton('contextLayer')"><span class="fa fa-circle" aria-hidden="true"></span></button>
        </td>
        <td>
            <button type="button" class="close" :title="title" v-on:click="remove" v-once><span aria-hidden="true">&times;</span></button>
        </td>
    </tr>
</template>

<script>
import Api from '../api/geoOverlays';
import {handleErrorResponse} from '../../geo/import';


export default {
    data() {
        return {
            browsingLayer: false,
            contextLayer: false,
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
            if(this.overlay.type === 'geotiff') {
                return Api.updateGeoTiff({id: this.volumeId, geo_overlay_id: this.overlay.id}, {
                    layer_type: dataKey,
                    value: !this[dataKey],
                    });
            } else {
                //this.overlay.type === 'webmap'
                return Api.updateWebMap({id: this.volumeId, webmap_overlay_id: this.overlay.id}, {
                    layer_type: dataKey,
                    value: !this[dataKey],
                    });
            }
        },
        // checks if string is too long and returns truncated version
        truncateString(str) {
            const n = 20;
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
        this.contextLayer = this.overlay.context_layer;
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
</style>