/**
 * World map displaying positions of all volume images
 */
biigle.$viewModel('volume-geo-map', function (element) {
    var volumeId = biigle.$require('geo.volume.id');
    var imageWithLabel = biigle.$require('geo.api.volumeImageWithLabel');

    new Vue({
        el: element,
        mixins: [biigle.$require('geo.mixins.geoMap')],
        data: {
            key: 'biigle.geo.imageSequence.' + volumeId,
        },
        computed: {
            selectedImages: function () {
                // These will be the selected images of previous sessions.
                // Vue will not be able to reactively update this property.
                return JSON.parse(localStorage.getItem(this.key)) || [];
            },
        },
        methods: {
            handleSelectedImages: function (ids) {
                if (ids.length > 0) {
                    localStorage.setItem(this.key, JSON.stringify(ids));
                } else {
                    localStorage.removeItem(this.key);
                }
            },
            getImageFilterApi: function (id) {
                return imageWithLabel.get({vid: volumeId, lid: id}, {});
            },
        },
    });
});
