/**
 * Dynamic information in the navbar of the geo show view
 */
biigle.$viewModel('geo-navbar', function (element) {
    var events = biigle.$require('biigle.events');
    var images = biigle.$require('geo.images');

    new Vue({
        el: element,
        data: {
            number: images.length,
            loading: false,
        },
        created: function () {
            var self = this;
            events.$on('loading.start', function () {
                self.loading = true;
            });

            events.$on('loading.stop', function () {
                self.loading = false;
            });

            events.$on('imageMap.update', function (images) {
                self.number = images.length;
            });
        }
    });
});
