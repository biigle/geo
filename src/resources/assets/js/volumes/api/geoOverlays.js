/**
 * Resource for geo overlays.
 *
 * var resource = biigle.$require('api.geoOverlays');
 *
 * Create a new geo overlay from plain data:
 * var data = new FormData(this.$refs.form);
 * resource.savePlain({volume_id: 1}, data).then(...);
 *
 * @type {Vue.resource}
 */
export default Vue.resource('api/v1/geo-overlays{/id}', {}, {
    // GeoTiff
    saveGeoTiff: {
        method: 'POST',
        url: 'api/v1/volumes{/id}/geo-overlays/geotiff',
    },
    updateGeoTiff: {
        method: 'PUT',
        url: 'api/v1/volumes{/id}/geo-overlays/geotiff{/geo_overlay_id}',
    },
    // getGeoTiff: {
    //     method: 'GET',
    //     url: 'api/v1/volumes{/id}/geo-overlays/{/layer_type?}',
    // },
    getGeoTiffOverlayUrlTemplate: {
        method: 'GET',
        url: 'api/v1/volumes{/id}/geo-overlays/geotiff/url',
    },
    // WebMap
    saveWebMap: {
        method: 'POST',
        url: 'api/v1/volumes{/id}/geo-overlays/webmap',
    }
});