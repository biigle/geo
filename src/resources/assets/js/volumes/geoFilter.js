import {VolumeFilters} from './import';
import {FilterList} from './import';

/**
 * Geo filter for the volume overview filters.
 */
if (Array.isArray(VolumeFilters)) {
    VolumeFilters.push({
        id: 'geo',
        label: 'geo selection',
        help: "All images that were (not) selected on the world map.",
        listComponent: {
            mixins: [FilterList],
            data() {
                return {
                    name: 'geo selection',
                };
            },
            created() {
                window.addEventListener('storage', () => {
                    this.$emit('refresh', this.rule);
                });
            },
        },
        getSequence(volumeId) {
            let key = 'biigle.geo.imageSequence.' + volumeId;

            return new Vue.Promise(function (resolve, reject) {
                resolve({data: JSON.parse(localStorage.getItem(key)) || []});
            });
        },
    });
}
