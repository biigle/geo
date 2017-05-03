/**
 * An element displaying the position of a single image on a map.
 *
 * @type {Object}
 */
biigle.$component('geo.components.imageMap', {
    template: '<div class="image-map"></div>',
    props: {
        images: {
            type: Array,
            required: true
        },
        preselected: {
            type: Array,
            default: function () {
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
        }
    },
    data: function () {
        return {
            source: new ol.source.Vector()
        };
    },
    computed: {
        features: function () {
            var preselected = {};
            this.preselected.forEach(function (p) {
                preselected[p] = null;
            });

            return this.images.map(function (image) {
                return new ol.Feature({
                    id: image.id,
                    // Determine if a feature should be initially selected.
                    preselected: preselected.hasOwnProperty(image.id),
                    geometry: new ol.geom.Point(ol.proj.fromLonLat([
                        image.lng,
                        image.lat
                    ])),
                });
            });
        }
    },
    methods: {
        parseSelectedFeatures: function (features) {
            return features.getArray().map(function (feature) {
                return feature.get('id');
            });
        },
        updateFeatures: function () {
            this.source.clear();
            this.source.addFeatures(this.features);
        }
    },
    created: function () {
        biigle.$require('biigle.events').$on('imageMap.update', this.updateFeatures);
    },
    mounted: function () {
        var style = biigle.$require('geo.ol.style');
        var events = biigle.$require('biigle.events');
        var self = this;

        // var source = new ol.source.Vector({features: features});
        this.source.addFeatures(this.features);
        var extent = this.source.getExtent();

        var tileLayer = new ol.layer.Tile({
          source: new ol.source.OSM()
        });

        var vectorLayer = new ol.layer.Vector({
            source: this.source,
            style: style.default,
            updateWhileAnimating: true,
            updateWhileInteracting: true
        });

        var map = new ol.Map({
            target: this.$el,
            layers: [tileLayer, vectorLayer],
            view: new ol.View(),
            interactions: ol.interaction.defaults({
                altShiftDragRotate: false,
                doubleClickZoom: this.interactive,
                keyboard: this.interactive,
                mouseWheelZoom: this.interactive,
                shiftDragZoom: false,
                dragPan: this.interactive,
                pinchRotate: false,
                pinchZoom: this.interactive,
            }),
            controls: ol.control.defaults({zoom: this.interactive}),
        });

        map.getView().fit(extent, map.getSize());

        if (this.zoom) {
            map.getView().setZoom(this.zoom);
        }

        if (this.interactive) {
            map.addControl(new ol.control.ScaleLine());

            map.addControl(new ol.control.ZoomToExtent({
                extent: extent,
                label: '\ue097'
            }));

            map.addControl(new ol.control.OverviewMap({
                collapsed: false,
                collapsible: false,
                layers: [tileLayer],
                view: new ol.View({zoom: 1, maxZoom: 1})
            }));
        }

        if (this.selectable) {
            var selectInteraction = new ol.interaction.Select({
                style: style.selected,
                features: this.features.filter(function (feature) {
                    return feature.get('preselected');
                })
            });
            var selectedFeatures = selectInteraction.getFeatures();
            map.addInteraction(selectInteraction);
            selectInteraction.on('select', function (e) {
                self.$emit('select', self.parseSelectedFeatures(selectedFeatures));
            });

            var dragBox = new ol.interaction.DragBox({
                condition: ol.events.condition.platformModifierKeyOnly
            });
            map.addInteraction(dragBox);
            dragBox.on('boxend', function () {
                selectedFeatures.clear();
                self.source.forEachFeatureIntersectingExtent(dragBox.getGeometry().getExtent(), function(feature) {
                    selectedFeatures.push(feature);
                });
                self.$emit('select', self.parseSelectedFeatures(selectedFeatures));
            });
        }

        events.$on('sidebar.toggle', function () {
            map.updateSize();
        });

        // Update once to get the correct map size in case the map is used in combination
        // with other Components (like the sidebar). Else the select interaction may not
        // work correctly.
        this.$nextTick(function () {
            map.updateSize();
        });
    }
});
