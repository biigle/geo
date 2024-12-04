/**
 * Resource for volume images with metadata.
 * 
 * Get image metadata:
 *
 * resource.query({id: volumeId}).then(...)
 *
 * @type {Vue.resource}
 */
export default Vue.resource('api/v1/volumes{/id}/images/metadata{/image_id}', {});
