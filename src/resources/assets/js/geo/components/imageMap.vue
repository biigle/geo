<template>
    <div class="image-map"></div>
</template>

<script>
import DragBox from 'ol/interaction/DragBox';
import Feature from 'ol/Feature';
import Map from 'ol/Map';
import OSMSource from 'ol/source/OSM';
import OverviewMap from 'ol/control/OverviewMap';
import Point from 'ol/geom/Point';
import ScaleLine from 'ol/control/ScaleLine';
import Select from 'ol/interaction/Select';
import Style from '../ol/style';
import TileLayer from 'ol/layer/Tile';
import TileWMS from 'ol/source/TileWMS.js';
import VectorLayer from 'ol/layer/Vector';
import VectorSource from 'ol/source/Vector';
import View from 'ol/View';
import ZoomToExtent from 'ol/control/ZoomToExtent';
import {defaults as defaultControls} from 'ol/control';
import {defaults as defaultInteractions} from 'ol/interaction';
import {Events} from '../import';
import {fromLonLat} from 'ol/proj';
import {platformModifierKeyOnly} from 'ol/events/condition';
import ZoomifySource from 'ol/source/Zoomify';


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
        }
    },
    data() {
        return {
            //
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
            return new TileLayer({
                source: new ZoomifySource({
                        url: this.overlayUrlTemplate.replaceAll(':id', overlay.id),
                        size: [overlay.attrs.width, overlay.attrs.height],
                        extent: [
                            overlay.attrs.top_left_lng,
                            overlay.attrs.bottom_right_lat,
                            overlay.attrs.bottom_right_lng,
                            overlay.attrs.top_left_lat,
                        ],
                        transition: 100,
                })
            });
        }
    },
    watch: {
        features(features) {
            this.source.clear();
            this.source.addFeatures(features);
        },
    },
    created() {
        // Set this directly so it is not made reactive.
        this.source = new VectorSource();
    },
    mounted() {
        this.source.addFeatures(this.features);
        let extent = this.source.getExtent();

        let tileLayer = new TileLayer({source: new OSMSource()});

        let vectorLayer = new VectorLayer({
            source: this.source,
            style: Style.default,
            updateWhileAnimating: true,
            updateWhileInteracting: true,
        });

        // let layers = [tileLayer];
        // Array.prototype.push.apply(layers, this.overlays);
        // layers.push(vectorLayer);

        // TODO:
        // this.map --> überall verfügbar (ohne reactive)
        // keine reactive variablen hinzufügen
        // Layer "unsichtbar" machen über opacity oder active
        this.map = new Map({
            target: this.$el,
            layers: [tileLayer],
            view: new View(),
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

        // include the WebMapService overlays as TileLayers (in reverse order so top layer gets added last)
        for(let overlay of this.overlays) {
            if(overlay.type == 'webmap') {
                let wmsTileLayer =  new TileLayer({
                    source: new TileWMS({
                        url: overlay.attrs.url,
                        params: {'LAYERS': overlay.attrs.layers, 'TILED': true},
                        serverType: 'geoserver',
                        transition: 0,
                    }),
                });
                this.map.addLayer(wmsTileLayer);
            } else { // if overlay.type == 'geotiff'
                // include the geotiff Layers as ol-tileLayer
                let tileLayer = this.createOverlayTile(overlay);
                tileLayer.set('name', `geotiffTile_${overlay.id}`);
                this.map.addLayer(tileLayer);
            }
        }

        this.map.addLayer(vectorLayer);

        this.map.getView().fit(extent, this.map.getSize());

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
                layers: [tileLayer],
                view: new View({zoom: 1, maxZoom: 1})
            }));
        }

        if (this.selectable) {
            let selectInteraction = new Select({
                style: Style.selected,
                features: this.features.filter((feature) => feature.get('preselected')),
            });
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
