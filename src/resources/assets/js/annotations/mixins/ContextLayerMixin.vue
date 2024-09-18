<script>
import TileLayer from 'ol/layer/Tile';
import TileWMS from 'ol/source/TileWMS';



/**
 * Mixin for the annotationCanvas component that contains logic for the Context-Layer (Geo-Overlay) interaction.
 *
 * @type {Object}
 */
export default {
    data() {
        return {
            overlays: [],
        };
    },
    methods: {

    },
    mounted() {
        this.overlays = biigle.$require('annotations.overlays');
        // console.log('geoOverlays: ', this.overlays);
        // console.log('activeId: ', this.settings);

        for(let i = this.overlays.length - 1; i >= 0; i--) {
                if(this.overlays[i].type == 'webmap') {
                    let wmsTileLayer =  new TileLayer({
                        source: new TileWMS({
                            url: this.overlays[i].attrs.url,
                            params: {'LAYERS': this.overlays[i].attrs.layers, 'TILED': true},
                            serverType: 'geoserver',
                            transition: 0,
                        }),
                    });
                    wmsTileLayer.set('id', this.overlays[i].id);
                    // this.map.push(wmsTileLayer);
                }
        }
    }
};
</script>