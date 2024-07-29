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
import VectorLayer from 'ol/layer/Vector';
import VectorSource from 'ol/source/Vector';
import View from 'ol/View';
import ZoomToExtent from 'ol/control/ZoomToExtent';
import {defaults as defaultControls} from 'ol/control';
import {defaults as defaultInteractions} from 'ol/interaction';
import {Events} from '../import';
import {fromLonLat} from 'ol/proj';
import {platformModifierKeyOnly} from 'ol/events/condition';

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
            },
        },
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

        let map = new Map({
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

        for(let layer of this.overlays) {
            map.addLayer(layer);
        }
        map.addLayer(vectorLayer);

        map.getView().fit(extent, map.getSize());

        if (this.zoom) {
            map.getView().setZoom(this.zoom);
        }

        if (this.interactive) {
            map.addControl(new ScaleLine());

            map.addControl(new ZoomToExtent({
                extent: extent,
                label: '\uf066'
            }));

            map.addControl(new OverviewMap({
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
            map.addInteraction(selectInteraction);
            selectInteraction.on('select', () => {
                this.$emit('select', this.parseSelectedFeatures(selectedFeatures));
            });

            let dragBox = new DragBox({condition: platformModifierKeyOnly});
            map.addInteraction(dragBox);
            dragBox.on('boxend', () => {
                selectedFeatures.clear();
                this.source.forEachFeatureIntersectingExtent(dragBox.getGeometry().getExtent(), function(feature) {
                    selectedFeatures.push(feature);
                });
                this.$emit('select', this.parseSelectedFeatures(selectedFeatures));
            });
        }

        Events.$on('sidebar.toggle', function () {
            map.updateSize();
        });

        // Update once to get the correct map size in case the map is used in combination
        // with other Components (like the sidebar). Else the select interaction may not
        // work correctly.
        this.$nextTick(function () {
            map.updateSize();
        });
    },
};
</script>
