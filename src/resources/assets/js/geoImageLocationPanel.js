/**
 * Panel to display an image location.
 */
biigle.$viewModel('geo-image-location-panel', function (element) {
    new Vue({
        el: element,
        data: {
            images: [biigle.geo.image]
        },
        components: {
            imageMap: biigle.geo.components.imageMap
        }
    });
});
