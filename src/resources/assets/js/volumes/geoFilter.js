import {VolumeFilters} from './import';
import {FilterList} from './import';
import {VolumesApi} from './import';
import FilterSelect from '../geo/components/filterByLocationComponent';
import {handleErrorResponse} from '../../../../../../../../resources/assets/js/core/messages/store';


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
                    placeholder: 'Label name',
                    text: "this is an example text",
                };
            },
            created() {
                VolumesApi.queryFilenames({id: this.volumeId})
                    .then(this.retrieveFiles, handleErrorResponse);
            },
        },
        getSequence(volumeId) {
            let key = 'biigle.geo.imageSequence.' + volumeId;
            let data = JSON.parse(localStorage.getItem(key)) || [];

            return new Vue.Promise.resolve({data});
        },
        // // add imageIds as variable
        // getSequence(volumeId) {
        //     let key = 'biigle.geo.imageSequence.' + volumeId;
        //     let data = JSON.parse(localStorage.getItem(key)) || [];

        //     return new Vue.Promise.resolve({data});
        // },
    });
}
