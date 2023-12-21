<script>
import Api from '../api/geoOverlays';
import {LoaderMixin} from '../import';


export default {
    mixins: [
        LoaderMixin,
    ],
    props: {
        volumeId: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            selectedTLLat: '',
            selectedTLLng: '',
            selectedBRLat: '',
            selectedBRLng: '',
            error: false,
            success: false,
        };
    },
    methods: {
        handleSuccess() {
            this.error = false;
            this.success = true;
        },
        handleError(response) {
            let knownError = response.body.errors && (
                    response.body.errors.geotiff || 
                    response.body.errors.modelTiePoints || 
                    response.body.errors.modelType || 
                    response.body.errors.affineTransformation
                );
            if (knownError) {
                if (Array.isArray(knownError)) {
                    this.error = knownError[0];
                } else {
                    this.error = knownError;
                }
            }
        },
        submitGeoTiff() {
            this.$refs.geoTiffInput.click();
        },
        upload(data) {
            return Api.saveGeoTiff({id: this.volumeId}, data);
        },
        uploadGeoTiff(event) {
            this.startLoading();
            let data = new FormData();
            data.append('geotiff', event.target.files[0]);
            data.append('volume', this.volumeId);
            this.upload(data)
                .then(this.handleSuccess, this.handleError)
                .finally(this.finishLoading);
        },
    }
}
</script>