<script>
import Api from '../api/geoOverlays';
import {handleErrorResponse} from '../import';
import {LoaderMixin} from '../import';


export default {
    mixins: [
        LoaderMixin,
    ],
    data() {
        return {
        };
    },
    methods: {
        submitGeoTiff() {
            this.$refs.geoTiffInput.click();
        },

        uploadGeoTiff(event) {
            this.startLoading();
            let data = new FormData();
            data.append('metadata_csv', event.target.files[0]);
            console.log("event: ", event);
            // this.upload(data)
            //     .then(this.handleSuccess, this.handleError)
            //     .finally(this.finishLoading);
        },
    }
}
</script>