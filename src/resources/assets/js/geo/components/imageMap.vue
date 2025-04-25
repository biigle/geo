<template>
    <div class="image-map"></div>
</template>

<script>
import DragBox from '@biigle/ol/interaction/DragBox';
import Feature from '@biigle/ol/Feature';
import Map from '@biigle/ol/Map';
import OSMSource from '@biigle/ol/source/OSM';
import OverviewMap from '@biigle/ol/control/OverviewMap';
import Point from '@biigle/ol/geom/Point';
import ScaleLine from '@biigle/ol/control/ScaleLine';
import Select from '@biigle/ol/interaction/Select';
import Style from '../ol/style.js';
import TileLayer from '@biigle/ol/layer/Tile';
import VectorLayer from '@biigle/ol/layer/Vector';
import VectorSource from '@biigle/ol/source/Vector';
import View from '@biigle/ol/View';
import ZoomToExtent from '@biigle/ol/control/ZoomToExtent';
import {defaults as defaultControls} from '@biigle/ol/control';
import {defaults as defaultInteractions} from '@biigle/ol/interaction';
import {Events} from '../import.js';
import {fromLonLat} from '@biigle/ol/proj';
import {platformModifierKeyOnly} from '@biigle/ol/events/condition';

/**
 * An element displaying the position of a single image on a map.
 *
 * @type {Object}
 */
export default {
    emits: ['select'],
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

        let map = new Map({
            target: this.$el,
            layers: [tileLayer, vectorLayer],
            view: new View({padding: [10, 10, 10, 10]}),
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

        map.getView().fit(extent, {padding: [10, 10, 10, 10]});

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
                layers: [new TileLayer({source: tileLayer.getSource()})],
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

        Events.on('sidebar.toggle', function () {
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
