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
biigle.$declare('api.geoOverlays', Vue.resource('api/v1/geo-overlays{/id}', {}, {
    savePlain: {
        method: 'POST',
        url: 'api/v1/volumes{/volume_id}/geo-overlays/plain',
    },
}));
