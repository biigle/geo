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
            selectedTLLat: '',
            selectedTLLng: '',
            selectedBRLat: '',
            selectedBRLng: '',
        };
    },
    methods: {
        submitGeoTiff() {
            this.$refs.geoTiffInput.click();
        },

        async uploadGeoTiff(event) {
            this.startLoading();
            let data = event.target.files[0];
            // this.upload(data)
            //     .then(this.handleSuccess, this.handleError)
            //     .finally(this.finishLoading);
        },
    }
}
</script>