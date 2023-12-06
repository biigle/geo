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
            fileIds: [],
        };
    },
    methods: {
        handleSelectedImages(ids) {
            if (ids.length > 0) {
                this.fileIds = ids;
            } else {
                this.fileIds = [];
            }
        },
        getImageFilterApi(id) {
            return Api.get({vid: this.volumeId, lid: id}, {});
        },
    },
    created() {
        this.volumeId = biigle.$require('geo.volume.id');
    },
};
</script>
