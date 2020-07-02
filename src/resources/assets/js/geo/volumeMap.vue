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
    computed: {
        selectedImages() {
            // These will be the selected images of previous sessions.
            // Vue will not be able to reactively update this property.
            return JSON.parse(localStorage.getItem(this.key)) || [];
        },
        key() {
            return 'biigle.geo.imageSequence.' + this.volumeId;
        },
    },
    methods: {
        handleSelectedImages(ids) {
            if (ids.length > 0) {
                localStorage.setItem(this.key, JSON.stringify(ids));
            } else {
                localStorage.removeItem(this.key);
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
