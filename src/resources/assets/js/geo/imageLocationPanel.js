import ImageMap from './components/imageMap';

/**
 * Panel to display an image location.
 */
export default {
    data: {
        images: [],
    },
    components: {
        imageMap: ImageMap,
    },
    created() {
        let image = biigle.$require('geo.image');
        this.images = [image];
    },
};
