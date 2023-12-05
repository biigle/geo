import {VolumeFilters} from './import';
import {FilterList} from './import';
import FilterSelect from '../geo/components/filterByLocationComponent';

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
            console.log("inspect selItem: ", data);
            return new Vue.Promise.resolve({data});
        }
    });
}
