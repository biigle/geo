/**
 * Resource for volume images with coordinates.
 *
 * var resource = biigle.$require('geo.api.imageWithLabel');
 *
 * Get image IDs:
 *
 * resource.query({id: volumeId}).then(...)
 *
 * @type {Vue.resource}
 */
export default Vue.resource('api/v1/volumes{/id}/coordinates', {});
