<script>
import { Collapse } from 'uiv';
import {Events, handleErrorResponse} from '../import';
import TileLayer from '@biigle/ol/layer/Tile';
import TileWMS from '@biigle/ol/source/TileWMS.js';
import ZoomifySource from '@biigle/ol/source/Zoomify';
import {Projection, getPointResolution, addCoordinateTransforms, transformExtent} from '@biigle/ol/proj';
import MetaApi from '../api/imageMetadata.js'
import {getHeight, getWidth, getCenter} from '@biigle/ol/extent';
import {getRenderPixel} from '@biigle/ol/render';
import {Icon, Style} from '@biigle/ol/style.js';
import Feature from '@biigle/ol/Feature.js';
import Point from '@biigle/ol/geom/Point.js'
import {Vector as VectorSource} from '@biigle/ol/source.js';
import VectorLayer from '@biigle/ol/layer/Vector.js';
import View from '@biigle/ol/View.js';



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
            metadata: [],
            imageLayer: null,
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

            if(this.activeOverlay != null && this.currentImage != null) {
                if(this.activeOverlay.type === 'webmap') {
                    tileLayer = new TileLayer({
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
        calculateExtent(targetExtent, imagePosX, imagePosY) {
            // shift the mosaic extent to fit to image coordinate 
            let shiftedTargetExtent = targetExtent.map(function(item, idx) {
                // [-imagePosX, -imagePosY, (max_x - imagePosX), (max_y - imagePosY)]
                return idx % 2 === 0 ? (item - imagePosX) : (item - imagePosY);
            });

            return shiftedTargetExtent;
        },
        // takes an overlay as input and returns ol-tileLayer in pixel-projection
        createOverlayTile(overlay) {
            let width = overlay.attrs.width;
            let height = overlay.attrs.height;
            // define the targetExtent (units = pixels)
            let extent = [0, 0, width, height];
            // define the source extent (EPSG:4326, units = degrees)
            let targetExtent = [
                overlay.attrs.top_left_lng, 
                overlay.attrs.top_left_lat, 
                overlay.attrs.bottom_right_lng, 
                overlay.attrs.bottom_right_lat
            ];
            
            // get the image geo-coordinates
            let lat = this.currentImage.lat;
            let lng = this.currentImage.lng;
            // transform the image coordinate from lat,lng to pixel
            let imagePosX = ((lng - targetExtent[0]) / getWidth(targetExtent)) * getWidth(extent);
            let imagePosY = ((lat - targetExtent[1]) / getHeight(targetExtent)) * getHeight(extent);
            // shift the extent by the calculated x,y position of the image
            // let shiftedExtent = this.calculateExtent(extent, imagePosX, imagePosY);
            
            
            // define a projection for each overlay (thus included id)
            let projection = new Projection({
                //needs to be same code as in annotationCanvas.vue in biigle/core
                code: 'biigle-image',
                units: 'pixels',
            });

            // add coordinate transforms between the source-projection and target projection (subtract image-coordinate)
            addCoordinateTransforms(
                'EPSG:4326',
                projection,
                ([x, y]) => [
                    (extent[0] +
                    ((x - targetExtent[0]) * getWidth(extent)) / getWidth(targetExtent)) - imagePosX,
                    (extent[1] +
                    ((y - targetExtent[1]) * getHeight(extent)) / getHeight(targetExtent)) - imagePosY,
                ],
                ([x, y]) => [
                    (targetExtent[0] +
                    ((x - extent[0]) * getWidth(targetExtent)) / getWidth(extent)) - lng,
                    (targetExtent[1] +
                    ((y - extent[1]) * getHeight(targetExtent)) / getHeight(extent)) - lat,
                ],
            );
            // calculate shifted extent
            let shiftedExtent = transformExtent(targetExtent, 'EPSG:4326', projection);
            
            let sourceLayer = new ZoomifySource({
                    url: this.overlayUrlTemplate.replaceAll(':id', overlay.id),
                    size: [overlay.attrs.width, overlay.attrs.height],
                    crossOrigin: 'anonymous',
                    zDirection: -1, // Ensure we get a tile with the screen resolution or higher
                    projection: projection,
                    extent: shiftedExtent,
            });

            // specify the point resolution in degrees through custom function 
            // set pointResolution on map-View manually
            projection.setGetPointResolution(
                (r) => r * Math.max(
                    getWidth(targetExtent) / getWidth(extent),
                    getHeight(targetExtent) / getHeight(extent)
                ),
            );

            let tileLayer = new TileLayer({
                source: sourceLayer,
            });


            // get unit/pixel ratio of mosaic and image
            let mosaicRatio = (targetExtent[2] - targetExtent[0]) / width;
            let imageRatio = this.currentImage.attrs.metadata.area / this.currentImage.attrs.width / this.currentImage.attrs.height;
            let scaleX = imageRatio / mosaicRatio;
            
            // define addCoordinateTransform function for rotation of layer
            let angle = this.currentImage.attrs.metadata.yaw;
            
            const lowLeft = new Feature({
                geometry: new Point([0, 0]),
            });

            const centerExt = new Feature({
                geometry: new Point(getCenter(shiftedExtent)),
            });

            lowLeft.setStyle(
                new Style({
                    image: new Icon({
                    color: 'rgba(255, 0, 0, 1)',
                    crossOrigin: 'anonymous',
                    src: 'https://img.icons8.com/?size=100&id=10028&format=png&color=FF0000',
                    scale: 0.2,
                    }),
                }),
            );

            centerExt.setStyle(
                new Style({
                    image: new Icon({
                    // color: 'rgba(255, 0, 0, 1)',
                    crossOrigin: 'anonymous',
                    src: 'https://img.icons8.com/?size=100&id=10028&format=png&color=00FF00',
                    scale: 0.2,
                    }),
                }),
            );

            const vectorSource = new VectorSource({
                features: [lowLeft, centerExt],
            });

            const vectorLayer = new VectorLayer({
                source: vectorSource,
            });
            this.map.addLayer(vectorLayer);

            // this.map.getView().setCenter([0, 0]);
            // this.map.getView().fit(shiftedExtent , this.map.getSize());

            // console.log('mosaic-ratio: ', mosaicRatio);
            // console.log('image-ratio: ', imageRatio);
            // console.log('scale-ratio', scaleX);

            // console.log('shiftedExtent: ', shiftedExtent);
            // console.log('map-size: ', this.map.getSize());
            // console.log('map-extent (after): ', this.map.getView().calculateExtent(this.map.getSize()));
            // console.log('shiftedExtent-center: ' , getCenter(shiftedExtent));
            // console.log('img-center: ', this.map.getView().getCenter());
            // console.log('angle: ', angle);
            // console.log('imageCoords: ', [imagePosX, imagePosY]);
            // console.log('shiftedExt-size: ', this.map.getView().calculateExtent([width, height]));

            // handle rotation
            tileLayer.on('prerender', (evt) => {
            let ratio = evt.frameState.pixelRatio;
            // this.map.getView().getCenter() <-- center of image
            let imageCenter = this.map.getView().getCenter();
            let mapPixel = this.map.getPixelFromCoordinate(imageCenter);
            // console.log('map: ', mapPixel);
            let ctx = evt.context;
            ctx.save();
            let canvasPixel = getRenderPixel(evt, mapPixel);
            // console.log(canvasPixel);
            ctx.translate(canvasPixel[0], canvasPixel[1]);
            // ctx.translate(mapPixel[0] * ratio, mapPixel[1] * ratio);
            // ctx.scale(1.5, 1.5, canvasPixel);
            // ctx.rotate(-20);
            // ctx.translate(-canvasPixel[0], -canvasPixel[1]);
            // //ctx.translate(-pixel[0] * ratio, -pixel[1] * ratio);
            });

            tileLayer.on('postrender', (evt) => {
            let ctx = evt.context;
            ctx.restore();
            });

            return tileLayer;
        },
        updateCurrentImage(id,) {
            // fetch the image metadata
            MetaApi.get({id: this.volumeId, image_id: id})
                .then(response => this.currentImage = response.body, handleErrorResponse);

            // retrieve the current image-layer
            // this.map.getLayers().forEach((mapLayer) => {
            //         if(mapLayer.get('name') === 'imageRegular' || mapLayer.get('name') === 'imageTile') {
            //             this.imageLayer = mapLayer;
            //             console.log('imageLayer-settingsTab: ', this.imageLayer.getExtent());
            //         }
            // });
        },
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
            if(layer !== null && this.currentImage !== null) {
                // let layerExists = false;

                // this.map.getLayers().forEach((mapLayer) => {
                //     if(mapLayer.get('name') === 'contextLayer') {
                //         // set visibility of contextLayers false (except currently active mapLayer)
                //         if(mapLayer.get('id') !== this.activeOverlay.id) {
                //             mapLayer.setVisible(false);
                //         } else {
                //             layerExists = true;
                //             // TODO: Does not update the layer in map view... 
                //             // refresh the source to update the mapLayer extent
                //             // this.$nextTick(function() {
                //             //     mapLayer.changed();
                //             //     mapLayer.getSource().refresh();
                //             // });
                //             layer.setOpacity(this.opacity);
                //             layer.setVisible(true);
                //         }
                //     }
                // });
                // // if layer does not exist yet, add it to map
                // if(!layerExists) {
                this.map.getLayers().removeAt(0);
                this.map.getLayers().insertAt(0, this.layer);
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