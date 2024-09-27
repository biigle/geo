<script>
import { Collapse } from 'uiv';
import {Events} from '../import';
import TileLayer from '@biigle/ol/layer/Tile';
import TileWMS from '@biigle/ol/source/TileWMS.js';

/**
 * The plugin component to edit the context-layer appearance.
 *
 * @type {Object}
 */
export default {
    components: {
        collapse: Collapse
    },
    props: {
        settings: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            opacityValue: '1',
            volumeId: null,
            overlays: null,
            showLayers: false,
            activeId: null,
            currentImage: null,
        }
    },
    computed: {
        opacity() {
            return parseFloat(this.opacityValue);
        },
        shown() {
            return this.opacity > 0;
        },
        // Implement OL-layer that shows mosaic
        layer() {
            let activeOverlay = this.overlays.find(x => x.id === this.activeId);

            if(activeOverlay.type == 'webmap') {
                let wmsTileLayer =  new TileLayer({
                        source: new TileWMS({
                            url: activeOverlay.attrs.url,
                            params: {'LAYERS': activeOverlay.attrs.layers, 'TILED': true},
                            serverType: 'geoserver',
                            transition: 0,
                        }),
                    });
                    wmsTileLayer.set('id', activeOverlay.id);
                    return wmsTileLayer;
            } else {
                // TODO: implement geoTIFF layer
            }
        }
    },
    methods: {
        toggleActive(id) {
            if(this.activeId === id) {
                // do nothing
            } else {
                this.activeId = id;
            }
        },
        updateCurrentImage(id, image) {
            this.currentImage = image;
        },
        extendMap(map) {
            map.addLayer(this.layer);
            // map.addInteraction(this.drawInteraction);
            // map.addInteraction(this.modifyInteraction);
        },
    },
    watch: {
        opacity(opacity) {
            if (opacity < 1) {
                this.settings.set('contextLayerOpacity', opacity);
            } else {
                this.settings.delete('contextLayerOpacity');
            }
            this.layer.setOpacity(opacity);
        },
    },
    created() {
        this.volumeId = biigle.$require('annotations.volumeId');
        this.overlays = biigle.$require('annotations.overlays');

        // check if there are context-overlays
        if(this.overlays.length !== 0) {
            // initially set activeId to first overlay
            this.activeId = this.overlays[0].id;
            // check if an opacity preference is available in settings and change it in case
            if (this.settings.has('contextLayerOpacity')) {
                this.opacityValue = this.settings.get('contextLayerOpacity');
            }
        }

        Events.$on('images.change', this.updateCurrentImage);
        Events.$on('annotations.map.init', this.extendMap);
    },
};
</script>
<style scoped>
    p {
        margin: 0;
    }

    /* layer-items settings */
    .list-group-item.active {
        background-color: #353535;
        color: #ffffff;
    }

    .custom {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        gap: 10px;
    }

    .custom > .ellipsis {
        order: 1;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .layer-button {
        display: flex;
        flex-flow: row nowrap;
        justify-content: space-between;
        align-items: center;
        background-color: var(--body-bg);
        width: 100%;
        text-decoration: none;
        border: none;
        color: inherit;
        padding-bottom: 15px;
    }

    /* animate the chevron icon when list expands */
    .icon {
        transition: ease-in-out .2s;
    }

    .icon.active {
        transform: rotateZ(180deg);
    }
</style>