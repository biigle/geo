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
        label: 'filter by location',
        help: "All images that were (not) selected on the world map.",
        listComponent: {
            mixins: [FilterList],
            data() {
                return {
                    name: 'filter by location',
                };
            },
            created() {
                // only triggered when called from different session/window
                window.addEventListener('storage', () => {
                    this.$emit('refresh', this.rule);
                });
                // trigger rule-refresh, see FilterSelect-Component of this filter
                window.addEventListener('storageUpdate', () => {
                    this.$emit('refresh', this.rule);
                });
            },
        },
        selectComponent: {
            mixins: [FilterSelect],
            components: {
            },
            data() {
                return {
                    text: "<em>Hint:</em> Select image locations on the volume map by drawing an encompassing rectangle. To do this, press and hold <kbd>Ctrl</kbd> as well as the left mouse button and move the cursor on the map.",
                };
            },
        },
        getSequence(volumeId) {
            let key = 'biigle.geo.filter.imageSequence.' + volumeId;
            let data = JSON.parse(sessionStorage.getItem(key)) || [];

            return new Vue.Promise.resolve({data});
        }
    });
}
