/**
 * Panel to display an image location.
 */
biigle.$viewModel('image-location-panel', function (element) {
    new Vue({
        el: element,
        components: {
            singleImageMap: biigle.geo.singleImageMap
        }
    });
});
