import {VolumeFilters} from './import';
import {FilterList} from './import';

/**
 * Geo filter for the volume overview filters.
 */
if (Array.isArray(VolumeFilters)) {
    VolumeFilters.push({
        id: 'geo',
        types: ['image'],
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
            let data = JSON.parse(localStorage.getItem(key)) || [];

            return new Vue.Promise.resolve({data});
        },
    });
}
