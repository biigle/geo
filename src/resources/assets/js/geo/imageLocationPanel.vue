<script>
import ImageMap from './components/imageMap.vue';

/**
 * Panel to display an image location.
 */
export default {
    data() {
        return {
            images: [],
        };
    },
    components: {
        imageMap: ImageMap,
    },
    created() {
        let image = biigle.$require('geo.image');
        this.images = [image];
    },
};
</script>
