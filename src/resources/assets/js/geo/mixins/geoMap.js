import ImageLayer from 'ol/layer/Image';
import ImageMap from '../components/imageMap';
import ImageStaticSource from 'ol/source/ImageStatic';
import {Events} from '../import';
import {handleErrorResponse} from '../import';

/**
 * Things that are used by both the project and volume geo map.
 */
export default {
    components: {
        imageMap: ImageMap,
    },
    data: {
        selectedLabels: [],
        filteredImageCache: {},
        allImages: [],
        baseOverlays: [],
        overlayUrl: '',
    },
    computed: {
        filteredImages() {
            let images = [];
            if (this.selectedLabels.length > 0) {
                let ids = this.filteredImageCache[this.selectedLabels[0]];
                Array.prototype.push.apply(images, ids);

                // Combine image IDs of all selected labels. This will result in an
                // OR operation. When labels A and B are selected, all images will be
                // displayed that contain annotations with label A *or* B.
                for (let i = this.selectedLabels.length - 1; i >= 0; i--) {
                    ids = this.filteredImageCache[this.selectedLabels[i]];
                    for (let j = ids.length - 1; j >= 0; j--) {
                        if (images.indexOf(ids[j]) === -1) {
                            images.push(ids[j]);
                        }
                    }
                }
            }

            return images;
        },
        images() {
            if (this.selectedLabels.length > 0) {
                return this.allImages.filter(
                    (item) => this.filteredImages.indexOf(item.id) !== -1
                );
            }

            return this.allImages;
        },
        overlays() {
            return this.baseOverlays.map(function (overlay) {
                return new ImageLayer({
                    source: new ImageStaticSource({
                        url: this.overlayUrl.replace(':id', overlay.id),
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
        addSelectedLabel(label) {
            if (this.selectedLabels.indexOf(label.id) === -1) {
                this.selectedLabels.push(label.id);
            }
        },
        handleSelectedLabel(label) {
            if (!this.filteredImageCache.hasOwnProperty(label.id)) {
                Events.$emit('loading.start');
                this.getImageFilterApi(label.id)
                    .then(
                        (response) => {
                            this.filteredImageCache[label.id] = response.data;
                            this.addSelectedLabel(label);
                        },
                        (response) => {
                            this.handleDeselectedLabel(label);
                            handleErrorResponse(response);
                        }
                    ).finally(function () {
                        Events.$emit('loading.stop');
                    });
            } else {
                this.addSelectedLabel(label);
            }
        },
        handleDeselectedLabel(label) {
            let index = this.selectedLabels.indexOf(label.id);
            if (index !== -1) {
                this.selectedLabels.splice(index, 1);
            }
        },
        handleClearedLabels() {
            this.selectedLabels.splice(0);
        },
    },
    watch: {
        images(images) {
            Events.$emit('imageMap.update', images);
        },
    },
    created() {
        this.allImages = biigle.$require('geo.images');
        this.baseOverlays = biigle.$require('geo.overlays');
        this.overlayUrl = biigle.$require('geo.overlayUrl');

        Events.$on('label.selected', this.handleSelectedLabel);
        Events.$on('label.deselected', this.handleDeselectedLabel);
        Events.$on('label.cleared', this.handleClearedLabels);
    },
};
