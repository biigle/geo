/**
 * Panel to display an image location.
 */
biigle.$viewModel('geo-image-location-panel', function (element) {
    var image = biigle.$require('geo.image');

    new Vue({
        el: element,
        data: {
            images: [image]
        },
        components: {
            imageMap: biigle.$require('geo.components.imageMap')
        }
    });
});
