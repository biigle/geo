<script>
import Api from '../api/geoOverlays';
import {handleErrorResponse} from '../import';
import {LoaderMixin} from '../import';
import {fromBlob} from 'geotiff';
import transformation from 'transform-coordinates';


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

        // converting from any coordinate system into WGS 84
        transformCRS(geoKeys) {
            let code = this.findCRSCode(geoKeys);
            console.log("code: ", `EPSG:${code}`);
            return transformation(`EPSG:${code.toString()}`, 'EPSG:4326'); // from whatever CRS to WGS 84
        },

        // get the CRS-code required to find the CRS-string from epsg-index,
        // which in turn is needed by proj4 to convert the coordinate-system   
        findCRSCode(geoKeys) {
            const code = geoKeys.ProjectedCSTypeGeoKey || geoKeys.GeographicTypeGeoKey;
                if (typeof code === 'undefined') {
                    return new Error('No ProjectedCSTypeGeoKey or GeographicTypeGeoKey provided');
                }
                return code;
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
            // transform from any Coordinate Reference System to WGS84
            let transform = this.transformCRS(geoTiffImage.geoKeys);
            let top_left = transform.forward({'x': bbox[0], 'y': bbox[1]});
            // let bottom_right = transform.forward({x: bbox[2], y: bbox[3]});
            console.log("top_left: ", top_left);
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