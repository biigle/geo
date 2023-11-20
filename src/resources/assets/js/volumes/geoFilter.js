import {VolumeFilters} from './import';
import {FilterList} from './import';
import FilterSelect from '../geo/components/filterByLocationComponent';
import Api from '../geo/api/volumeImageWithCoord';

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
                window.addEventListener('storage', () => {
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
                    text: "this is an example text",
                };
            },
            created() {
                // get all image + coordinate information from volume-images 
                Api.get({id: this.volumeId}, {})
                    .then(
                        (response) => {
                            this.gotItems(response);
                    });
            }
        },
        getSequence(volumeId) {
            let key = 'biigle.geo.imageSequence.' + volumeId;
            let data = JSON.parse(localStorage.getItem(key)) || [];

            return new Vue.Promise.resolve({data});
        }
    });
}
