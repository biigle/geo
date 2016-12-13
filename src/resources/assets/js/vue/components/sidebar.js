/**
 * A collapsible sidebar that can show different content "tabs"
 *
 * @type {Object}
 */
biigle.geo.components.sidebar = {
    template: '<aside class="sidebar" :class="classObject">' +
        '<div class="sidebar__buttons"><slot name="buttons"></slot></div>' +
        '<div class="sidebar__tabs"><slot name="tabs"></slot></div>' +
    '</aside>',
    data: function () {
        return {
            openName: null,
        };
    },
    computed: {
        open: function () {
            return this.openName !== null;
        },
        classObject: function () {
            return {
                'sidebar--open': this.open
            };
        }
    },
    mounted: function () {
        var self = this;
        this.$on('toggle', function (name) {
            this.openName = (this.openName === name) ? null : name;
            this.$emit('open', this.openName);
            // Use setTimeout so the event is handled *after* the sidebar expanded/
            // collapsed.
            setTimeout(function () {
                biigle.geo.events.$emit('sidebar.toggle', self.open);
            });
        });
    }
};
