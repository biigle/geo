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
            labelTrees: biigle.$require('geo.components.labelTrees'),
        },
        methods: {
            handleSidebarToggle: function () {
                // Use setTimeout so the event is handled *after* the sidebar expanded/
                // collapsed.
                setTimeout(function () {
                    events.$emit('sidebar.toggle');
                });
            }
        }
    });
});
