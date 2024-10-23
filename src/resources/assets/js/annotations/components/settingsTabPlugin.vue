<script>
import { Collapse } from 'uiv';
import {Events, handleErrorResponse} from '../import';
import TileLayer from '@biigle/ol/layer/Tile';
import TileWMS from '@biigle/ol/source/TileWMS.js';
import ZoomifySource from '@biigle/ol/source/Zoomify';
import {Projection, addProjection, addCoordinateTransforms} from '@biigle/ol/proj';
import MetaApi from '../api/imageMetadata.js'
import {getHeight, getWidth, getCenter} from '@biigle/ol/extent';
import {rotate} from '@biigle/ol/coordinate';


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

            if(this.activeOverlay !== null && this.currentImage !== null) {
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
        // georeference coordinate (pixel -> CRS)
        transformCoordinate(extent, width, height, x, y) {
            // Compute the parameters of the georeference affine transformation
            let A = (extent[2] - extent[0]) / width; // pixel size in the x-direction in map units/pixel
            let B = 0; // rotation about x-axis
            let C = extent[0]; // x-coordinate of the center of the upper left pixel
            let D = 0; // rotation about y-axis
            let E = -(extent[3] - extent[1]) / height; // pixel size in the y-direction in map units, almost always negative
            let F = extent[3]; // y-coordinate of the center of the upper left pixel

            // transform x,y pixel-coordinate in CRS coordinate
            let geoX = A*x + B*y + C;
            let geoY = D*x + E*y + F;

            return [geoX, geoY];
        },
        // helper function to rotate coordinates
        rotateCoordinate(coordinate, angle, anchor) {
            let coord = rotate([coordinate[0] - anchor[0], coordinate[1] - anchor[1]], angle);
            return [coord[0] + anchor[0], coord[1] + anchor[1]];
        },
        rotateProjection(projection, angle, targetExtent, extent) {
            let rotateTransform = (coordinate) => {
                return this.rotateCoordinate(coordinate, angle, getCenter(targetExtent))
            }
            let normalTransform = (coordinate) => {
                return this.rotateCoordinate(coordinate, -angle, getCenter(targetExtent))
            }
            
            let rotatedProjection = new Projection({
                code: 'biigle-image', // + ':rotation:' + angle.toString(),
                units: projection.getUnits(),
                extent: targetExtent,
                getPointResolution: (r) => r * Math.max(
                    getWidth(targetExtent) / getWidth(extent),
                    getHeight(targetExtent) / getHeight(extent)
                ),
            });

            addProjection(rotatedProjection);

            addCoordinateTransforms(
                projection,
                rotatedProjection,
                rotateTransform,
                normalTransform
            );

            // addCoordinateTransforms(
            //     projection,
            //     'EPSG:4326',
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
            //     ],
            // );

            // addCoordinateTransforms(
            //     "EPSG:4326",
            //     rotatedProjection,
            //     function(coordinate) {
            //         return rotateTransform(transform(coordinate, "EPSG:4326", projection));
            //     },
            //     function(coordinate) {
            //         return transform(normalTransform(coordinate), projection, "EPSG:4326");
            //     }
            // );

            // addCoordinateTransforms(
            //     'EPSG:3857',
            //     rotatedProjection,
            //     function (coordinate) {
            //     return rotateTransform(transform(coordinate, 'EPSG:3857', projection));
            //     },
            //     function (coordinate) {
            //     return transform(normalTransform(coordinate), projection, 'EPSG:3857');
            //     }
            // );

            return rotatedProjection;
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
            // define the source extent (EPSG:4326, units = degrees)
            let extent = [
                overlay.attrs.top_left_lng, 
                overlay.attrs.top_left_lat, 
                overlay.attrs.bottom_right_lng, 
                overlay.attrs.bottom_right_lat
            ];
            
            // get unit/pixel ratio of mosaic and image
            let mosaicRatio = (extent[2] - extent[0]) / width;
            let imageRatio = this.currentImage.attrs.metadata.area / this.currentImage.attrs.width / this.currentImage.attrs.height;
            
            // define the targetExtent (units = pixels)
            let targetExtent = [0, 0, width, height];

            // console.log(getWidth(extent), ' / ', width, ' = ', mosaicRatio);
            // console.log('mosaic: ', mosaicRatio);
            // console.log('image: ', imageRatio);

            // get the image geo-coordinates
            let lat = this.currentImage.lat;
            let lng = this.currentImage.lng;
            // define addCoordinateTransform function for rotation of layer
            let angle = this.currentImage.attrs.metadata.yaw;
            // transform the image coordinate from lat,lng to pixel
            let imagePosX = ((lng - extent[0]) / getWidth(extent)) * getWidth(targetExtent);
            let imagePosY = ((lat - extent[1]) / getHeight(extent)) * getHeight(targetExtent);
            
            // shift the target extent based on coordinate of image
            let shiftedTargetExtent = this.calculateExtent(targetExtent, imagePosX, imagePosY);
            
            // define a projection for each overlay (thus included id)
            let projection = new Projection({
                //needs to be same code as in annotationCanvas.vue in biigle/core
                code: 'biigle-image',
                units: 'pixels',
                extent: shiftedTargetExtent,
                getPointResolution: (r) => r * Math.max(
                    getWidth(targetExtent) / getWidth(extent),
                    getHeight(targetExtent) / getHeight(extent)
                ),
            });

            // specify the point resolution in meters through custom function 
            // (default transforms the point from pixel to EPSG:4326, units = degrees)

            let sourceLayer = new ZoomifySource({
                    url: this.overlayUrlTemplate.replaceAll(':id', overlay.id),
                    size: [overlay.attrs.width, overlay.attrs.height],
                    crossOrigin: 'anonymous',
                    zDirection: -1, // Ensure we get a tile with the screen resolution or higher
                    projection: angle ? this.rotateProjection(projection, angle, shiftedTargetExtent, extent) : projection,
                    extent: shiftedTargetExtent,
            });
            
            // set pointResolution on map-View manually
            // const currentView = this.map.getView();
            // const currentProjection = currentView.getProjection();
            // currentProjection.setGetPointResolution(
            //     (r) => r * Math.max(
            //         getWidth(targetExtent) / getWidth(extent),
            //         getHeight(targetExtent) / getHeight(extent)
            //     ),
            // );

            let tileLayer = new TileLayer({
                source: sourceLayer,
            });

            return tileLayer;
        },
        updateCurrentImage(id,) {
            // fetch the image metadata
            MetaApi.get({id: this.volumeId, image_id: id})
                .then(response => this.currentImage = response.body, handleErrorResponse);

            // // retrieve the current image-layer
            // this.map.getLayers().forEach((mapLayer) => {
            //         if(mapLayer.get('name') === 'imageRegular' || mapLayer.get('name') === 'imageTile') {
            //             this.imageLayer = mapLayer;
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

                // SET MAP VIEW
                // const currentView = this.map.getView();
                // const currentProjection = currentView.getProjection();
                // const currentResolution = currentView.getResolution();
                // const currentCenter = this.map.getView().getCenter();
                // let scale = 6.0 / this.currentImage.attrs.metadata.gps_altitude;
                // let currentPointResolution = getPointResolution(currentProjection, 1 / scale, currentCenter, 'm') * scale;
                // const newPointResolution = getPointResolution(this.layer.getSource().getProjection(), 1 / scale, currentCenter, 'm') * scale;
                // const newResolution = (currentResolution * currentPointResolution) / newPointResolution;
                
                // console.log('scale: ', scale);
                // console.log('curr-point-resolution: ', currentPointResolution);
                // console.log('new-point-resolution: ', newPointResolution);
                // console.log('currRes: ', currentResolution);
                // console.log('newRes:', newResolution);
                // set new resolution based on image scale
                // this.map.updateSize();
                // this.map.getView().setResolution(scaleResolution);
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