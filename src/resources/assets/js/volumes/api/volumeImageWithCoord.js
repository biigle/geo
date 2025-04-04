import { Resource } from '../import.js';

/**
 * Resource for volume images with coordinates.
 *
 * var resource = biigle.$require('geo.api.imageWithLabel');
 *
 * Get image IDs:
 *
 * resource.query({id: volumeId}).then(...)
 */
export default Resource('api/v1/volumes{/id}/coordinates', {});
