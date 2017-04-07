/**
 * Geo filter for the volume overview filters.
 */
(function () {
    var key = 'biigle.geo.imageSequence.' + biigle.$require('volumes.volumeId');
    var bus = biigle.$require('volumes.events');

    var filter = {
        id: 'geo',
        label: 'geo selection',
        help: "All images that were (not) selected on the world map.",
        listComponent: {
            mixins: [biigle.$require('volumes.components.filterListComponent')],
            data: function () {
                return {name: 'geo selection'};
            },
        },
        getSequence: function (volumeId) {
            return new Vue.Promise(function (resolve, reject) {
                resolve({data: JSON.parse(localStorage.getItem(key)) || []});
            });
        }
    };

    biigle.$require('volumes.stores.filters').push(filter);
    window.addEventListener('storage', function () {
        bus.$emit('filters.refresh', filter.id);
    });
})();
