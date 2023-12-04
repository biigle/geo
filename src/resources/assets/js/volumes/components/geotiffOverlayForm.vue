<script>
import Api from '../api/geoOverlays';
import {handleErrorResponse} from '../import';
import {LoaderMixin} from '../import';
import GeoTIFF, {fromBlob} from 'geotiff';


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
            let tiff = await fromBlob(event.target.files[0]);
            // to obtain the most detailed image. 
            let geoTiffImage = await tiff.getImage(0);
            let bbox = geoTiffImage.getBoundingBox(); // [minx, miny, maxx, maxy]
            console.log("data: ", tiff);
            console.log("geoTiffImage: ", geoTiffImage);
            console.log("bbox: ", bbox);
            console.log("geoKeys: ", await geoTiffImage.getGeoKeys());
            console.log("gdal-info: ", await geoTiffImage.getGDALMetadata());
            let data = new FormData();
            data.append('image', geoTiffImage);
            data.append('top_left_lat', bbox[1]);
            data.append('top_left_long', bbox[0]);
            data.append('bottom_right_lat', bbox[3]);
            data.append('bottom_right_long', bbox[2]);


            // this.upload(data)
            //     .then(this.handleSuccess, this.handleError)
            //     .finally(this.finishLoading);
        },
    }
}
</script>