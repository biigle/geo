/**
 * The geo overlay upload of the volume edit page.
 */
biigle.$viewModel('volume-geo-overlay-upload', function (element) {
    var messages = biigle.$require('messages.store');
    var resource = biigle.$require('api.geoOverlays');

    var overlayItem = {
        props: ['overlay'],
        computed: {
            classObject: function () {
                return {'list-group-item-success': this.overlay.isNew};
            },
            title: function () {
                return 'Delete overlay ' + this.overlay.name;
            },
        },
        methods: {
            remove: function () {
                if (confirm('Are you sure you want to delete the overlay ' + this.overlay.name + '?')) {
                    this.$emit('remove', this.overlay);
                }
            },
        },
    };

    new Vue({
        el: element,
        mixins: [
            biigle.$require('core.mixins.loader'),
            biigle.$require('core.mixins.editor'),
        ],
        components: {
            tabs: VueStrap.tabs,
            tab: VueStrap.tab,
            overlayItem: overlayItem,
            plainOverlayForm: biigle.$require('geo.volumes.components.plainOverlayForm'),
        },
        data: {
            overlays: biigle.$require('volumes.geoOverlays'),
        },
        computed: {
            classObject: function () {
                return {'panel-warning panel--editing': this.editing};
            },
            hasOverlays: function () {
                return this.overlays.length > 0;
            },
        },
        methods: {
            addOverlay: function (overlay) {
                overlay.isNew = true;
                this.overlays.unshift(overlay);
                this.finishLoading();
            },
            handleRemove: function (overlay) {
                this.startLoading();
                var self = this;
                resource.delete({id: overlay.id})
                    .then(function () {self.overlayRemoved(overlay);})
                    .catch(messages.handleErrorResponse)
                    .finally(this.finishLoading);
            },
            overlayRemoved: function (overlay) {
                var overlays = this.overlays;
                for (var i = overlays.length - 1; i >= 0; i--) {
                    if (overlays[i].id === overlay.id) {
                        overlays.splice(i, 1);
                        return;
                    }
                }
            },
        },
    });
});
