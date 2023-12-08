<script>
import Api from './api/volumeImageWithLabel';
import GeoMap from './mixins/geoMap';

/**
 * World map displaying positions of all volume images
 */

export default {
    mixins: [GeoMap],
    data() {
        return {
            volumeId: null,
        };
    },
    methods: {
        getImageFilterApi(id) {
            return Api.get({vid: this.volumeId, lid: id}, {});
        },
    },
    created() {
        this.volumeId = biigle.$require('geo.volume.id');
    },
};
</script>
