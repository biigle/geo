import { Resource } from '../import.js';

/**
 * Resource for project images having annotations with a certain label.
 *
 * var resource = biigle.$require('geo.api.imageWithLabel');
 *
 * Get image IDs:
 *
 * resource.query({pid: projectId, lid: labelId}, {}).then(...)
 */
export default Resource('api/v1/projects{/pid}/images/filter/annotation-label{/lid}');
