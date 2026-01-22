<script>
import Api from './api/projectImageWithLabel.js';
import GeoMap from './mixins/geoMap.vue';

/**
 * World map displaying positions of all project images
 */
export default {
    extends: GeoMap,
    data() {
        return {
            projectId: null,
        };
    },
    methods: {
        getImageFilterApi(id) {
            return Api.get({ pid: this.projectId, lid: id }, {});
        },
    },
    created() {
        this.projectId = biigle.$require('geo.project.id');
    },
};
</script>
