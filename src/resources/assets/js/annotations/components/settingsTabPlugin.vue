<script>
import { Collapse } from 'uiv';
import {Events} from '../import';
import TileLayer from '@biigle/ol/layer/Tile';
import TileWMS from '@biigle/ol/source/TileWMS.js';
import ZoomifySource from '@biigle/ol/source/Zoomify';
import {Projection} from '@biigle/ol/proj';

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
            overlayUrlTemplate: '',
            loaded: false,
        }
    },
    computed: {
        opacity() {
            return parseFloat(this.opacityValue);
        },
        shown() {
            return this.opacity > 0;
        },
        // return the geo-overlay matching the currently active ID
        activeOverlay() {
            if(this.overlays !== null) {
                return this.overlays.find(x => x.id === this.activeId);
            }
            return null;
        },
        // Implement OL-layer that shows mosaic
        layer() {
            let tileLayer = null;

            if(this.activeOverlay !== null) {
                if(this.activeOverlay.type === 'webmap') {
                    tileLayer =  new TileLayer({
                            source: new TileWMS({
                                url: this.activeOverlay.attrs.url,
                                params: {'LAYERS': this.activeOverlay.attrs.layers, 'TILED': true},
                                serverType: 'geoserver',
                                transition: 0,
                            }),
                        });
                } else {
                    // geoTIFF layer
                    tileLayer = this.createOverlayTile(this.activeOverlay);
                }
                tileLayer.set('id', this.activeOverlay.id);
                tileLayer.set('name', 'contextLayer');
                tileLayer.setOpacity(this.opacity);
            }

            return tileLayer;
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
        // takes an overlay as input and returns ol-tileLayer in pixel-projection
        createOverlayTile(overlay) {
            // define a projection for each overlay (thus included id)
            let projection = new Projection({
                code: 'biigle-image',
                units: 'pixels',
            });

            let sourceLayer = new ZoomifySource({
                    url: this.overlayUrlTemplate.replaceAll(':id', overlay.id),
                    size: [overlay.attrs.width, overlay.attrs.height],
                    crossOrigin: 'anonymous',
                    zDirection: -1, // Ensure we get a tile with the screen resolution or higher
                    projection: projection
            });

            // let extentPixel = [
            // $top_left = [0, 0];
            // $bottom_left = [0, $height];
            // $top_right = [$width, 0];
            // $bottom_right = [$width, $height];
            // ];

            // let extentEPSG4326 = [
            //     overlay.attrs.top_left_lng, 
            //     overlay.attrs.top_left_lat, 
            //     overlay.attrs.bottom_right_lng, 
            //     overlay.attrs.bottom_right_lat
            // ];
            // define the source extent (units = pixels) and targetExtent (EPSG:3857, units = meters)
            // let extent = sourceLayer.getTileGrid().getExtent();
            // let targetExtent = sourceLayer.getTileGrid().getExtent();

            // // specify the point resolution in meters through custom function 
            // // (default transforms the point from pixel to EPSG:4326, units = degrees)
            // projection.setGetPointResolution(
            //     (r) => r * Math.max(
            //                 getWidth(targetExtent) / getWidth(extent),
            //                 getHeight(targetExtent) / getHeight(extent)
            //             )
            // );

            // // add coordinate transforms between the source-projection and target projection (same as view-projection)
            // addCoordinateTransforms(
            //     projection,
            //     'EPSG:3857',
            //     ([x, y]) => [
            //         targetExtent[0] +
            //         ((x - extent[0]) * getWidth(targetExtent)) / getWidth(extent),
            //         targetExtent[1] +
            //         ((y - extent[1]) * getHeight(targetExtent)) / getHeight(extent),
            //     ],
            //     ([x, y]) => [
            //         extent[0] +
            //         ((x - targetExtent[0]) * getWidth(extent)) / getWidth(targetExtent),
            //         extent[1] +
            //         ((y - targetExtent[1]) * getHeight(extent)) / getHeight(targetExtent),
            //     ]
            // );
            
            let tileLayer = new TileLayer({
                source: sourceLayer,
            });

            return tileLayer;
        },
        updateCurrentImage(id, image) {
            this.currentImage = image;
        }
    },
    watch: {
        // save the ID of the currently selected overlay in settings
        activeId(activeId) {
            this.settings.set(`${this.volumeId}-contextLayerId`, activeId);
        },
        opacity(opacity) {
            if (opacity < 1) {
                this.settings.set(`${this.volumeId}-contextLayerOpacity`, opacity);
            } else {
                this.settings.delete(`${this.volumeId}-contextLayerOpacity`);
            }
        },
        // change layer on map instance upon changes
        layer(layer) {
            if(layer !== null) {
                this.map.addLayer(layer);
            }
        }
    },
    created() {
        this.volumeId = biigle.$require('annotations.volumeId');
        this.overlays = biigle.$require('annotations.overlays');
        this.overlayUrlTemplate = biigle.$require('annotations.overlayUrlTemplate');
        this.map = null;

        // define the names on volume-basis
        const contextLayerId = `${this.volumeId}-contextLayerId`;
        const contextLayerOpacity = `${this.volumeId}-contextLayerOpacity`;
        // check if there are context-overlays
        if(this.overlays.length !== 0) {
            if(this.settings.has(contextLayerId)) {
                this.activeId = this.settings.get(contextLayerId);
            } else {
                // initially set activeId to first overlay
                this.activeId = this.overlays[0].id;
            }
            // check if an opacity preference is available in settings and change it in case
            if (this.settings.has(contextLayerOpacity)) {
                this.opacityValue = this.settings.get(contextLayerOpacity);
            }
        }

        Events.$on('images.change', this.updateCurrentImage);
        Events.$on('annotations.map.init', (map) => {
            this.map = map;
        });
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