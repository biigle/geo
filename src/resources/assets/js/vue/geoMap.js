/**
 * World map displaying positions of all transect images
 */
biigle.$viewModel('geo-map', function (element) {
    new Vue({
        el: element,
        data: {
            images: biigle.$require('geo.images'),
            key: 'biigle.geo.imageSequence.' + biigle.$require('geo.transect.id')
        },
        computed: {
            selectedImages: function () {
                // These will be the selected images of previous sessions.
                // Vue will not be able to reactively update this property.
                return JSON.parse(localStorage.getItem(this.key)) || [];
            }
        },
        components: {
            imageMap: biigle.$require('geo.components.imageMap'),
        },
        methods: {
            handleSelectedImages: function (ids) {
                if (ids.length > 0) {
                    localStorage.setItem(this.key, JSON.stringify(ids));
                } else {
                    localStorage.removeItem(this.key);
                }
            }
        }
    });
});
