/**
 * Geo filter for the volume overview filters.
 */
if (Array.isArray(biigle.$require('volumes.stores.filters'))) {
    biigle.$require('volumes.stores.filters').push({
        id: 'geo',
        label: 'geo selection',
        help: "All images that were (not) selected on the world map.",
        listComponent: {
            mixins: [biigle.$require('volumes.components.filterListComponent')],
            data() {
                return {name: 'geo selection'};
            },
            created() {
                var self = this;
                window.addEventListener('storage', function () {
                    self.$emit('refresh', self.rule);
                });
            },
        },
        getSequence(volumeId) {
            var key = 'biigle.geo.imageSequence.' + volumeId;

            return new Vue.Promise(function (resolve, reject) {
                resolve({data: JSON.parse(localStorage.getItem(key)) || []});
            });
        }
    });
}
