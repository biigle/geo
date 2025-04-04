<script>
import ImageMap from '../components/imageMap.vue';
import {Events} from '../import.js';
import {handleErrorResponse} from '../import.js';
import {LabelTrees} from '../import.js';
import {SidebarTab} from '../import.js';
import {Sidebar} from '../import.js';

/**
 * Things that are used by both the project and volume geo map.
 */
export default {
    components: {
        imageMap: ImageMap,
        sidebar: Sidebar,
        sidebarTab: SidebarTab,
        labelTrees: LabelTrees,
    },
    data: function() {
        return {
            selectedLabels: [],
            filteredImageCache: {},
            allImages: [],
            labelTrees: [],
        };
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
        imageCount() {
            return this.images.length;
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
                Events.emit('loading.start');
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
                        Events.emit('loading.stop');
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
        handleSidebarToggle() {
            // Use nextTick so the event is handled *after* the sidebar expanded/
            // collapsed.
            this.$nextTick(function () {
                Events.emit('sidebar.toggle');
            });
        },
    },
    watch: {
        imageCount(count) {
            Events.emit('imageMap.update', count);
        },
    },
    created() {
        this.allImages = biigle.$require('geo.images');
        this.labelTrees = biigle.$require('geo.labelTrees');
    },
};
</script>
