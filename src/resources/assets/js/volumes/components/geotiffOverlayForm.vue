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
            selectedFile: null,
        };
    },
    methods: {
        submitGeoTiff() {
            this.$refs.geoTiffInput.click();
        },

        uploadGeoTiff(event) {
            this.startLoading();
            this.selectedFile = event.target.files[0];
            console.log("data: ", this.selectedFile);
            // this.upload(data)
            //     .then(this.handleSuccess, this.handleError)
            //     .finally(this.finishLoading);
        },
    }
}
</script>