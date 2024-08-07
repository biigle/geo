import Circle from '@biigle/ol/style/Circle';
import Fill from '@biigle/ol/style/Fill';
import Stroke from '@biigle/ol/style/Stroke';
import Style from '@biigle/ol/style/Style';

let style = {
    colors: {
        blue: '#0099ff',
        white: '#ffffff',
        orange: '#ff5e00',
    },
    radius: {
        default: 6,
    },
    strokeWidth: {
        default: 2,
    },
};

style.default = new Style({
    image: new Circle({
        radius: style.radius.default,
        fill: new Fill({color: style.colors.blue}),
        stroke: new Stroke({
            color: style.colors.white,
            width: style.strokeWidth.default,
        }),
    }),
});

style.selected = new Style({
    image: new Circle({
        radius: style.radius.default,
        fill: new Fill({color: style.colors.orange}),
        stroke: new Stroke({
            color: style.colors.white,
            width: style.strokeWidth.default,
        }),
    }),
    zIndex: Infinity,
});

export default style;
