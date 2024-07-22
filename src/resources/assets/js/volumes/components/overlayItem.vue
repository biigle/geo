<template>
    <tr>
        <td class="handle">
            <i class="fas fa-grip-lines"></i>
        </td>
        <td scope="row" class="start">
            <span class="text-muted">#<span>{{ overlay.id }}</span></span>
        </td>
        <td class="start">
            <span class="ellipsis" v-text="overlay.name"></span>
        </td>
        <td>
            <!-- browsing-layer -->
            <button type="button" class="toggle-btn" :class="{active: browsingLayer}" v-on:click=toggleBrowsingButton()><span class="fa fa-circle" aria-hidden="true"></span></button>
        </td>
        <td>
            <!-- context layer -->
            <button type="button" class="toggle-btn" :class="{active: contextLayer}" v-on:click=toggleContextButton()><span class="fa fa-circle" aria-hidden="true"></span></button>
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
        toggleBrowsingButton() {
            Api.updateGeoTiff({id: this.volumeId, geo_overlay_id: this.overlay.id}, {
                    browsing_layer: !this.browsingLayer,
                })
                .then((response) => {
                    if(response.status == 200) {
                        this.browsingLayer = !this.browsingLayer;
                    }
                })
                .catch(handleErrorResponse);
        },
        toggleContextButton() {
            // TODO: update the data in the geoOverlay database 
            Api.updateGeoTiff({id: this.volumeId, geo_overlay_id: this.overlay.id}, {
                    context_layer: !this.contextLayer,
                })
                .then((response) => {
                    if(response.status == 200) {
                        this.contextLayer = !this.contextLayer;
                    }
                })
                .catch(handleErrorResponse);
        },
    },
    mounted() {
        // initially set the two values 
        this.browsingLayer = this.overlay.browsing_layer;
        this.contextLayer = this.overlay.context_layer;
    }
};
</script>