/**
 * World map displaying positions of all transect images
 */
biigle.$viewModel('geo-map', function (element) {
    new Vue({
        el: element,
        data: {
            images: biigle.geo.images
        },
        components: {
            imageMap: biigle.geo.components.imageMap
        },
        methods: {
            handleSelectedImages: function (ids) {
                console.log(ids);
            }
        }
    });
});
