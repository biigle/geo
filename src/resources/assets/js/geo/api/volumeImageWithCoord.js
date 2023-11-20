/**
 * Resource for volume images having annotations with a certain label.
 *
 * var resource = biigle.$require('geo.api.imageWithLabel');
 *
 * Get image IDs:
 *
 * resource.query({vid: volumeId, lid: labelId}, {}).then(...)
 *
 * @type {Vue.resource}
 */
export default Vue.resource('api/v1/volumes{/id}/files/filter/file-coordinates', {});
