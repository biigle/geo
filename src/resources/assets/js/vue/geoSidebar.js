/**
 * The sidebar of the geo show view
 */
biigle.$viewModel('geo-sidebar', function (element) {
    var events = biigle.$require('geo.events');

    new Vue({
        el: element,
        data: {
            labelTrees: biigle.$require('geo.labelTrees'),
        },
        components: {
            sidebar: biigle.$require('geo.components.sidebar'),
            sidebarTab: biigle.$require('geo.components.sidebarTab'),
            labelTrees: biigle.$require('labelTrees.components.labelTrees'),
        },
        methods: {
            handleSidebarToggle: function () {
                // Use nextTick so the event is handled *after* the sidebar expanded/
                // collapsed.
                this.$nextTick(function () {
                    events.$emit('sidebar.toggle');
                });
            },
            handleSelect: function (label) {
                events.$emit('label.selected', label);
            },
            handleDeselect: function (label) {
                events.$emit('label.deselected', label);
            },
            handleCleared: function () {
                events.$emit('label.cleared');
            }
        }
    });
});
