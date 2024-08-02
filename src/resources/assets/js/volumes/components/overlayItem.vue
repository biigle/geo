<template>
    <tr>
        <td class="handle">
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
            <button type="button" class="close" v-on:click="remove" v-once><span aria-hidden="true">&times;</span></button>
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
        index: {type: Number, required: true}
    },
    computed: {
        classObject() {
            return {
                'list-group-item-success': this.overlay.isNew,
            };
        },
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
            Api.updateGeoTiff({id: this.volumeId, geo_overlay_id: this.overlay.id}, {
                layer_type: dataKey,
                value: !this[dataKey],
                })
                .then((response) => {
                    if(response.status == 200) {
                        this[dataKey] = !this[dataKey];
                    }
                })
                .catch(handleErrorResponse);
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