biigle.$declare('geo.ol.style', function () {
    var style = {
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

    if (ol) {
        style.default = new ol.style.Style({
            image: new ol.style.Circle({
                radius: style.radius.default,
                fill: new ol.style.Fill({
                    color: style.colors.blue
                }),
                stroke: new ol.style.Stroke({
                    color: style.colors.white,
                    width: style.strokeWidth.default
                })
            })
        });

        style.selected = new ol.style.Style({
            image: new ol.style.Circle({
                radius: style.radius.default,
                fill: new ol.style.Fill({
                    color: style.colors.orange
                }),
                stroke: new ol.style.Stroke({
                    color: style.colors.white,
                    width: style.strokeWidth.default
                })
            }),
            zIndex: Infinity
        });
    }

    return style;
});
