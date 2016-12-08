/**
 * An element displaying the position of a single image on a map.
 *
 * @type {Object}
 */
biigle.geo.singleImageMap = {
    template: '<div></div>',
    props: {
        lng: {
            type: Number,
            required: true
        },
        lat: {
            type: Number,
            required: true
        },
        interactive: {
            type: Boolean,
            default: true
        }
    },
    mounted: function () {
        var source = new ol.source.Vector({
            features: [
                new ol.Feature({
                    geometry: new ol.geom.Point(ol.proj.fromLonLat([this.lng, this.lat]))
                })
            ]
        });

        new ol.Map({
            target: this.$el,
            layers: [
                // ArcGIS ocean map (restricted license!)
                // new ol.layer.Tile({
                //     source: new ol.source.XYZ({
                //         url: "http://services.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/{z}/{y}/{x}",
                //     })
                // }),
                new ol.layer.Tile({
                  source: new ol.source.OSM()
                }),
                new ol.layer.Vector({
                    source: source,
                    style: new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 6,
                            fill: new ol.style.Fill({
                                color: [0, 153, 255, 1]
                            }),
                            stroke: new ol.style.Stroke({
                                color: 'white',
                                width: 2
                            })
                        })
                    }),
                    updateWhileAnimating: true,
                    updateWhileInteracting: true
                })
            ],
            view: new ol.View({
                center: ol.proj.fromLonLat([this.lng, this.lat]),
                zoom: 4
            }),
            interactions: ol.interaction.defaults({
                altShiftDragRotate: false,
                doubleClickZoom: this.interactive,
                keyboard: this.interactive,
                mouseWheelZoom: this.interactive,
                shiftDragZoom: this.interactive,
                dragPan: this.interactive,
                pinchRotate: false,
                pinchZoom: this.interactive,
            }),
            controls: ol.control.defaults({
                zoom: this.interactive
            }),
        });
    }
};
