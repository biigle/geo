
if (ol) {
    biigle.geo.ol.style = {
        colors: {
            blue: '#0099ff',
            white: '#ffffff',
            orange: '#ff5e00'
        },
        radius: {
            default: 6
        },
        strokeWidth: {
            default: 2
        }
    };

    biigle.geo.ol.style.default = new ol.style.Style({
        image: new ol.style.Circle({
            radius: biigle.geo.ol.style.radius.default,
            fill: new ol.style.Fill({
                color: biigle.geo.ol.style.colors.blue
            }),
            stroke: new ol.style.Stroke({
                color: biigle.geo.ol.style.colors.white,
                width: biigle.geo.ol.style.strokeWidth.default
            })
        })
    });

    biigle.geo.ol.style.selected = new ol.style.Style({
        image: new ol.style.Circle({
            radius: biigle.geo.ol.style.radius.default,
            fill: new ol.style.Fill({
                color: biigle.geo.ol.style.colors.orange
            }),
            stroke: new ol.style.Stroke({
                color: biigle.geo.ol.style.colors.white,
                width: biigle.geo.ol.style.strokeWidth.default
            })
        }),
        zIndex: Infinity
    });
}
