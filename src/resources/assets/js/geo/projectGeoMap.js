/**
 * World map displaying positions of all project images
 */
biigle.$viewModel('project-geo-map', function (element) {
    var projectId = biigle.$require('geo.project.id');
    var imageWithLabel = biigle.$require('geo.api.projectImageWithLabel');

    new Vue({
        el: element,
        mixins: [biigle.$require('geo.mixins.geoMap')],
        methods: {
            getImageFilterApi: function (id) {
                return imageWithLabel.get({pid: projectId, lid: id}, {});
            },
        },
    });
});
