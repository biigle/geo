import {Events} from './import';

/**
 * Dynamic information in the navbar of the geo show view
 */
export default {
    data: {
        images: [],
        loading: false,
    },
    computed: {
        number() {
            return this.images.length;
        },
    },
    created() {
        this.images = biigle.$require('geo.images');

        Events.$on('loading.start', () => {
            this.loading = true;
        });

        Events.$on('loading.stop', () => {
            this.loading = false;
        });

        Events.$on('imageMap.update', (images) => {
            this.images = images;
        });
    },
};
