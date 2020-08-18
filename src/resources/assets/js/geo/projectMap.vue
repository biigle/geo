<script>
import Api from './api/projectImageWithLabel';
import GeoMap from './mixins/geoMap';

/**
 * World map displaying positions of all project images
 */
export default {
    mixins: [GeoMap],
    data() {
        return {
            projectId: null,
        };
    },
    methods: {
        getImageFilterApi(id) {
            return Api.get({pid: this.projectId, lid: id}, {});
        },
    },
    created() {
        this.projectId = biigle.$require('geo.project.id');
    },
};
</script>
