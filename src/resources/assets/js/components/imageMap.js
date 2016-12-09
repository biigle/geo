/**
 * An element displaying the position of a single image on a map.
 *
 * @type {Object}
 */
biigle.geo.components.imageMap = {
    template: '<div class="image-map"></div>',
    props: {
        images: {
            type: Array,
            required: true
        },
        interactive: {
            type: Boolean,
            default: true
        },
        zoom: {
            type: Number
        },
        cluster: {
            type: Boolean,
            default: false
        },
        selectable: {
            type: Boolean,
            default: false
        }
    },
    methods: {
        extractFeatureId: function (feature) {
            return feature.get('id');
        },
        parseSelectedFeatures: function (features) {
            var output = [];
            features.forEach(function (feature) {
                if (this.cluster && feature.get('features')) {
                    Array.prototype.push.apply(output, feature.get('features').map(this.extractFeatureId));
                } else {
                    output.push(this.extractFeatureId(feature));
                }

            }, this);

            return output;
        }
    },
    mounted: function () {
        var features = [];
        var self = this;

        for (var i = this.images.length - 1; i >= 0; i--) {
            features.push(new ol.Feature({
                id: this.images[i].id,
                geometry: new ol.geom.Point(ol.proj.fromLonLat([
                    this.images[i].lng,
                    this.images[i].lat
                ]))
            }));
        }

        var source = new ol.source.Vector({features: features});
        var extent = source.getExtent();

        if (this.cluster) {
            source = new ol.source.Cluster({
                source: source,
                distance: 5
            });
        }

        var tileLayer = new ol.layer.Tile({
          source: new ol.source.OSM()
        });

        var vectorLayer = new ol.layer.Vector({
            source: source,
            style: biigle.geo.ol.style.default,
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
                shiftDragZoom: this.interactive,
                dragPan: this.interactive,
                pinchRotate: false,
                pinchZoom: this.interactive,
                dragZoom: false
            }),
            controls: ol.control.defaults({zoom: this.interactive}),
        });

        map.addControl(new ol.control.ScaleLine());
        map.getView().fit(extent, map.getSize());

        if (this.zoom) {
            map.getView().setZoom(this.zoom);
        }

        if (this.interactive) {
            map.addControl(new ol.control.ZoomToExtent({
                extent: extent,
                label: '\ue097',
                tipLabel: 'Reset Zoom'
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
                style: biigle.geo.ol.style.selected
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
                source.forEachFeatureIntersectingExtent(dragBox.getGeometry().getExtent(), function(feature) {
                    selectedFeatures.push(feature);
                });
                self.$emit('select', self.parseSelectedFeatures(selectedFeatures));
            });
        }
    }
};
