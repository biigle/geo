import {VolumeFilters} from './import.js';
import {FilterList} from './import.js';
import FilterSelect from '../volumes/components/filterByLocationComponent.vue';

/**
 * Geo filter for the volume overview filters.
 */

if (Array.isArray(VolumeFilters)) {
    VolumeFilters.push({
        id: 'location',
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
        },
        selectComponent: {
            mixins: [FilterSelect],
            components: {
            },
            data() {
                return {
                };
            },
        },
        getSequence(volumeId, data) {
            return new Vue.Promise.resolve({data});
        }
    });
}
