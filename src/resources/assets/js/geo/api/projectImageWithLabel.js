/**
 * Resource for project images having annotations with a certain label.
 *
 * var resource = biigle.$require('geo.api.imageWithLabel');
 *
 * Get image IDs:
 *
 * resource.query({pid: projectId, lid: labelId}, {}).then(...)
 *
 * @type {Vue.resource}
 */
biigle.$declare('geo.api.projectImageWithLabel', Vue.resource(
    'api/v1/projects{/pid}/images/filter/annotation-label{/lid}', {}
));
