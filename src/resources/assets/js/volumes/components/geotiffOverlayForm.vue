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
        };
    },
    methods: {
        submitGeoTiff() {
            this.$refs.geoTiffInput.click();
        },
        upload(data) {
            return Api.saveGeoTiff({id: this.volumeId}, data);
        },
        uploadGeoTiff(event) {
            this.startLoading();
            let data = new FormData();
            data.append('metadata_geotiff', event.target.files[0]);
            this.upload(data)
                .then(this.handleSuccess, this.handleError)
                .finally(this.finishLoading);
        },
    }
}
</script>