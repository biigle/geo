/**
 * Resource for images having annotations with a certain label.
 *
 * var resource = biigle.$require('geo.api.imageWithLabel');
 *
 * Get image IDs:
 *
 * resource.query({tid: transectId, lid: labelId}, {}).then(...)
 *
 * @type {Vue.resource}
 */
biigle.$declare('geo.api.imageWithLabel', Vue.resource(
    '/api/v1/transects{/tid}/images/filter/annotation-label{/lid}', {}
));
