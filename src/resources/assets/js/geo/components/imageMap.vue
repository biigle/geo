<template>
    <div class="image-map"></div>
</template>

<script>
import DragBox from '@biigle/ol/interaction/DragBox';
import Feature from '@biigle/ol/Feature';
import LayerGroup from '@biigle/ol/layer/Group';
import Map from '@biigle/ol/Map';
import OSMSource from '@biigle/ol/source/OSM';
import OverviewMap from '@biigle/ol/control/OverviewMap';
import Point from '@biigle/ol/geom/Point';
import Projection from '@biigle/ol/proj/Projection';
import ScaleLine from '@biigle/ol/control/ScaleLine';
import Select from '@biigle/ol/interaction/Select';
import Style from '../ol/style';
import TileLayer from '@biigle/ol/layer/Tile';
import TileWMS from '@biigle/ol/source/TileWMS.js';
import VectorLayer from '@biigle/ol/layer/Vector';
import VectorSource from '@biigle/ol/source/Vector';
import View from '@biigle/ol/View';
import ZoomifySource from '@biigle/ol/source/Zoomify';
import ZoomToExtent from '@biigle/ol/control/ZoomToExtent';
import {defaults as defaultControls} from '@biigle/ol/control';
import {defaults as defaultInteractions} from '@biigle/ol/interaction';
import {Events} from '../import';
import {fromLonLat} from '@biigle/ol/proj';
import {platformModifierKeyOnly} from '@biigle/ol/events/condition';
import TileGrid from '@biigle/ol/tilegrid/TileGrid.js';

/**
 * An element displaying the position of a single image on a map.
 *
 * @type {Object}
 */
export default {
    props: {
        images: {
            type: Array,
            required: true
        },
        preselected: {
            type: Array,
            default() {
                return [];
            },
        },
        interactive: {
            type: Boolean,
            default: true
        },
        zoom: {
            type: Number
        },
        selectable: {
            type: Boolean,
            default: false
        },
        overlays: {
            type: Array,
            default() {
                return [];
            }
        },
        overlayUrlTemplate: {
            type: String,
            default() {
                return '';
            }
        },
        activeIds: {
            type: Array,
            default() {
                return [];
            }
        }
    },
    data() {
        return {
            extent: [],
        };
    },
    computed: {
        imagesWithGps() {
            return this.images.filter(function (image) {
                return image.lat !== null && image.lng !== null;
            });
        },
        features() {
            let preselected = {};
            this.preselected.forEach(function (p) {
                preselected[p] = null;
            });

            return this.imagesWithGps.map(function (image) {
                return new Feature({
                    id: image.id,
                    // Determine if a feature should be initially selected.
                    preselected: preselected.hasOwnProperty(image.id),
                    geometry: new Point(fromLonLat([image.lng, image.lat])),
                });
            });
        }
    },
    methods: {
        parseSelectedFeatures(features) {
            return features.getArray().map((feature) => feature.get('id'));
        },
        // takes array of overlays as input and returns them as ol-tileLayers
        createOverlayTile(overlay) {
            let top_left = fromLonLat([
                overlay.attrs.top_left_lng,
                overlay.attrs.top_left_lat,
            ]);
            let bottom_right = fromLonLat([
                overlay.attrs.bottom_right_lng,
                overlay.attrs.bottom_right_lat,
            ]);

            // let extentPixels = [0, 0, overlay.attrs.width, overlay.attrs.height];
            let extentEPSG4326 = [
                overlay.attrs.top_left_lng, 
                overlay.attrs.top_left_lat, 
                overlay.attrs.bottom_right_lng, 
                overlay.attrs.bottom_right_lat
            ];
            let extentEPSG3857 = [
                top_left[0],
                top_left[1],
                bottom_right[0],
                bottom_right[1]
            ];
            
            let projection = new Projection({
                code: 'EPSG:3857',
                units: 'm',
            });

            let sourceLayer = new ZoomifySource({
                    url: this.overlayUrlTemplate.replaceAll(':id', overlay.id),
                    size: [overlay.attrs.width, overlay.attrs.height],
                    // projection: projection,
                    // extent: extentEPSG3857
            });

            let maxZoom = sourceLayer.getTileGrid().getMaxZoom();
            let res = []
            let i = 0;
            // calculate full size resolution
            let resolution = (bottom_right[0] - top_left[0]) / overlay.attrs.width;
            while(i <= maxZoom) {
                res.unshift(resolution);
                resolution *= 2;
                ++i;
            }

            let tileGrid = new TileGrid({
                extent: extentEPSG3857,
                resolutions: res,
                maxZoom: maxZoom
            });
            sourceLayer.setTileGridForProjection('EPSG:3857', tileGrid);
            
            let tileLayer = new TileLayer({
                source: sourceLayer,
            });

            this.extent = extentEPSG3857;
            // console.log('zoomify-extent: ', sourceLayer.getTileGrid().getExtent());
            // console.log('zoomify-resolutions: ', sourceLayer.getTileGrid().getResolutions());
            // console.log('zoomify-tileGrid: ', sourceLayer.getTileGrid());


            return tileLayer;
        }
    },
    watch: {
        features(features) {
            this.source.clear();
            this.source.addFeatures(features);
        },
        // set the visibility of overlay-layer based on activeIds Array
        activeIds(idArray) {
            this.overlayGroup.getLayers().forEach((layer) => {
                if(idArray.includes(layer.getProperties().id)) {
                    layer.setVisible(true);
                } else {
                    layer.setVisible(false);
                }
            });
        }
    },
    created() {
        // Set this directly so it is not made reactive.
        this.source = new VectorSource();
    },
    mounted() {
        this.source.addFeatures(this.features);
        let extent = this.source.getExtent();

        let basemap = new TileLayer({source: new OSMSource()});

        let vectorLayer = new VectorLayer({
            source: this.source,
            style: Style.default,
            updateWhileAnimating: true,
            updateWhileInteracting: true,
        });

        this.overlayGroup = new LayerGroup({
                layers: [],
                name: 'overlayGroup'
        });

        // include the WebMapService overlays as TileLayers (in reverse order so top layer gets added last)
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
                this.overlayGroup.getLayers().push(wmsTileLayer);
            } else { // if overlay.type == 'geotiff'
                // include the geotiff Layers as ol-tileLayer
                let tileLayer = this.createOverlayTile(this.overlays[i]);
                tileLayer.set('name', `geotiffTile_${this.overlays[i].id}`);
                tileLayer.set('id', this.overlays[i].id);
                this.overlayGroup.getLayers().push(tileLayer);
            }
        }
       
        // this.map makes map available in methods without being reactive
        this.map = new Map({
            target: this.$el,
            layers: [basemap, this.overlayGroup],
            view: new View({
                projection: 'EPSG:3857',
                padding: [10, 10, 10, 10],
            }),
            interactions: defaultInteractions({
                altShiftDragRotate: false,
                doubleClickZoom: this.interactive,
                keyboard: this.interactive,
                mouseWheelZoom: this.interactive,
                shiftDragZoom: false,
                dragPan: this.interactive,
                pinchRotate: false,
                pinchZoom: this.interactive,
            }),
            controls: defaultControls({zoom: this.interactive}),
        });

        this.map.addLayer(vectorLayer);

        this.map.getView().fit(this.extent, this.map.getSize());

        if (this.zoom) {
            this.map.getView().setZoom(this.zoom);
        }

        if (this.interactive) {
            this.map.addControl(new ScaleLine());

            this.map.addControl(new ZoomToExtent({
                extent: extent,
                label: '\uf066'
            }));

            this.map.addControl(new OverviewMap({
                collapsed: false,
                collapsible: false,
                layers: [new TileLayer({source: basemap.getSource()})],
                view: new View({zoom: 1, maxZoom: 1})
            }));
        }

        if (this.selectable) {
            let selectInteraction = new Select({
                style: Style.selected,
            });
            selectInteraction
                .getFeatures()
                .extend(this.features.filter(feature => feature.get('preselected')));
            let selectedFeatures = selectInteraction.getFeatures();
            this.map.addInteraction(selectInteraction);
            selectInteraction.on('select', () => {
                this.$emit('select', this.parseSelectedFeatures(selectedFeatures));
            });

            let dragBox = new DragBox({condition: platformModifierKeyOnly});
            this.map.addInteraction(dragBox);
            dragBox.on('boxend', () => {
                selectedFeatures.clear();
                this.source.forEachFeatureIntersectingExtent(dragBox.getGeometry().getExtent(), function(feature) {
                    selectedFeatures.push(feature);
                });
                this.$emit('select', this.parseSelectedFeatures(selectedFeatures));
            });
        }

        Events.$on('sidebar.toggle', function () {
            this.map.updateSize();
        });

        // Update once to get the correct map size in case the map is used in combination
        // with other Components (like the sidebar). Else the select interaction may not
        // work correctly.
        this.$nextTick(function () {
            this.map.updateSize();
        });
    },
};
</script>
