/**
 * World map displaying positions of all transect images
 */
biigle.$viewModel('geo-map', function (element) {
    var events = biigle.$require('geo.events');
    var transectId = biigle.$require('geo.transect.id');
    var imageWithLabel = biigle.$require('geo.api.imageWithLabel');
    var messages = biigle.$require('messages.store');

    new Vue({
        el: element,
        data: {
            allImages: biigle.$require('geo.images'),
            filteredImages: [],
            key: 'biigle.geo.imageSequence.' + transectId,
            hasSelectedLabel: false,
            filteredImageCache: {}
        },
        computed: {
            selectedImages: function () {
                // These will be the selected images of previous sessions.
                // Vue will not be able to reactively update this property.
                return JSON.parse(localStorage.getItem(this.key)) || [];
            },
            images: function () {
                if (this.hasSelectedLabel) {
                    var self = this;
                    return this.allImages.filter(function (item) {
                        return self.filteredImages.indexOf(item.id) !== -1;
                    });
                }

                return this.allImages;
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
            },
            handleSelectedLabel: function (label) {
                if (label) {
                    if (!this.filteredImageCache.hasOwnProperty(label.id)) {
                        events.$emit('loading.start');
                        this.filteredImageCache[label.id] = imageWithLabel.get({tid: transectId, lid: label.id}, {});
                        this.filteredImageCache[label.id].finally(function () {
                            events.$emit('loading.stop');
                        });
                    }
                    this.hasSelectedLabel = true;
                    this.filteredImageCache[label.id].then(this.setSelectedImages, messages.handleErrorResponse);
                } else {
                    this.hasSelectedLabel = false;
                    this.emitUpdate();
                }
            },
            setSelectedImages: function (response) {
                this.filteredImages.splice(0);
                Array.prototype.push.apply(this.filteredImages, response.data);
                this.emitUpdate();
            },
            emitUpdate: function () {
                // Wait until the changed images are propagated down to the image-map.
                this.$nextTick(function () {
                    events.$emit('imageMap.update', this.images);
                });
            }
        },
        created: function () {
            events.$on('label.selected', this.handleSelectedLabel);
        }
    });
});
