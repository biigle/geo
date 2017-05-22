/**
 * World map displaying positions of all volume images
 */
biigle.$viewModel('geo-map', function (element) {
    var events = biigle.$require('events');
    var volumeId = biigle.$require('geo.volume.id');
    var imageWithLabel = biigle.$require('geo.api.imageWithLabel');
    var messages = biigle.$require('messages.store');
    var overlayUrl = biigle.$require('geo.overlayUrl');

    new Vue({
        el: element,
        data: {
            allImages: biigle.$require('geo.images'),
            filteredImages: [],
            selectedLabels: [],
            key: 'biigle.geo.imageSequence.' + volumeId,
            filteredImageCache: {},
            baseOverlays: biigle.$require('geo.overlays'),
        },
        computed: {
            selectedImages: function () {
                // These will be the selected images of previous sessions.
                // Vue will not be able to reactively update this property.
                return JSON.parse(localStorage.getItem(this.key)) || [];
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
            overlays: function () {
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
            addSelectedLabel: function (label) {
                if (this.selectedLabels.indexOf(label.id) === -1) {
                    this.selectedLabels.push(label.id);
                    this.updateFilteredImages();
                }
            },
            handleSelectedLabel: function (label) {
                if (!this.filteredImageCache.hasOwnProperty(label.id)) {
                    events.$emit('loading.start');
                    imageWithLabel.get({tid: volumeId, lid: label.id}, {}).bind(this)
                        .then(function (response) {
                            this.filteredImageCache[label.id] = response.data;
                            this.addSelectedLabel(label);
                        }, function (response) {
                            this.handleDeselectedLabel(label);
                            messages.handleErrorResponse(response);
                        }).finally(function () {
                            events.$emit('loading.stop');
                        });
                } else {
                    this.addSelectedLabel(label);
                }
            },
            handleDeselectedLabel: function (label) {
                var index = this.selectedLabels.indexOf(label.id);
                if (index !== -1) {
                    this.selectedLabels.splice(index, 1);
                    this.updateFilteredImages();
                }
            },
            handleClearedLabels: function () {
                this.selectedLabels.splice(0);
                this.updateFilteredImages();
            },
            updateFilteredImages: function () {
                var ids;
                this.filteredImages.splice(0);

                if (this.selectedLabels.length > 0) {
                    ids = this.filteredImageCache[this.selectedLabels[0]];
                    Array.prototype.push.apply(this.filteredImages, ids);

                    // Combine image IDs of all selected labels. This will result in an
                    // OR operation. When labels A and B are selected, all images will be
                    // displayed that contain annotations with label A *or* B.
                    for (var i = this.selectedLabels.length - 1; i >= 0; i--) {
                        ids = this.filteredImageCache[this.selectedLabels[i]];
                        for (var j = ids.length - 1; j >= 0; j--) {
                            if (this.filteredImages.indexOf(ids[j]) === -1) {
                                this.filteredImages.push(ids[j]);
                            }
                        }
                    }
                }

                // Wait until the changed images are propagated down to the image-map.
                this.$nextTick(function () {
                    events.$emit('imageMap.update', this.images);
                });
            },
        },
        created: function () {
            events.$on('label.selected', this.handleSelectedLabel);
            events.$on('label.deselected', this.handleDeselectedLabel);
            events.$on('label.cleared', this.handleClearedLabels);
        }
    });
});
