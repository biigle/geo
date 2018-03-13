/**
 * Things that are used by both the project and volume geo map.
 */
biigle.$declare('geo.mixins.geoMap', {
    components: {
        imageMap: biigle.$require('geo.components.imageMap'),
    },
    data: {
        selectedLabels: [],
        filteredImageCache: {},
    },
    computed: {
        events: function () {
            return biigle.$require('events');
        },
        allImages: function () {
            return biigle.$require('geo.images');
        },
        filteredImages: function () {
            var images = [];
            if (this.selectedLabels.length > 0) {
                ids = this.filteredImageCache[this.selectedLabels[0]];
                Array.prototype.push.apply(images, ids);

                // Combine image IDs of all selected labels. This will result in an
                // OR operation. When labels A and B are selected, all images will be
                // displayed that contain annotations with label A *or* B.
                for (var i = this.selectedLabels.length - 1; i >= 0; i--) {
                    ids = this.filteredImageCache[this.selectedLabels[i]];
                    for (var j = ids.length - 1; j >= 0; j--) {
                        if (images.indexOf(ids[j]) === -1) {
                            images.push(ids[j]);
                        }
                    }
                }
            }

            return images;
        },
        images: function () {
            if (this.selectedLabels.length > 0) {
                var self = this;
                return this.allImages.filter(function (item) {
                    return self.filteredImages.indexOf(item.id) !== -1;
                });
            }

            return this.allImages;
        },
        baseOverlays: function () {
            return biigle.$require('geo.overlays');
        },
        overlays: function () {
            var overlayUrl = biigle.$require('geo.overlayUrl');

            return this.baseOverlays.map(function (overlay) {
                return new ol.layer.Image({
                    source: new ol.source.ImageStatic({
                        url: overlayUrl.replace('{id}', overlay.id),
                        imageExtent: [
                            overlay.top_left_lng,
                            overlay.bottom_right_lat,
                            overlay.bottom_right_lng,
                            overlay.top_left_lat,
                        ],
                        projection: 'EPSG:4326',
                    }),
                });
            });
        },
    },
    methods: {
        addSelectedLabel: function (label) {
            if (this.selectedLabels.indexOf(label.id) === -1) {
                this.selectedLabels.push(label.id);
            }
        },
        handleSelectedLabel: function (label) {
            if (!this.filteredImageCache.hasOwnProperty(label.id)) {
                this.events.$emit('loading.start');
                this.getImageFilterApi(label.id).bind(this)
                    .then(function (response) {
                        this.filteredImageCache[label.id] = response.data;
                        this.addSelectedLabel(label);
                    }, function (response) {
                        this.handleDeselectedLabel(label);
                        biigle.$require('messages.store').handleErrorResponse(response);
                    }).finally(function () {
                        this.events.$emit('loading.stop');
                    });
            } else {
                this.addSelectedLabel(label);
            }
        },
        handleDeselectedLabel: function (label) {
            var index = this.selectedLabels.indexOf(label.id);
            if (index !== -1) {
                this.selectedLabels.splice(index, 1);
            }
        },
        handleClearedLabels: function () {
            this.selectedLabels.splice(0);
        },
    },
    watch: {
        images: function (images) {
            this.events.$emit('imageMap.update', images);
        },
    },
    created: function () {
        this.events.$on('label.selected', this.handleSelectedLabel);
        this.events.$on('label.deselected', this.handleDeselectedLabel);
        this.events.$on('label.cleared', this.handleClearedLabels);
    },
});
