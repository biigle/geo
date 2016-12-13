/**
 * The sidebar of the geo show view
 */
biigle.$viewModel('geo-sidebar', function (element) {
    new Vue({
        el: element,
        data: {
            labelTrees: biigle.geo.labelTrees,
        },
        components: {
            sidebar: biigle.geo.components.sidebar,
            sidebarTab: biigle.geo.components.sidebarTab,
            labelTrees: biigle.geo.components.labelTrees,
        },
        methods: {
            handleSidebarToggle: function () {
                // Use setTimeout so the event is handled *after* the sidebar expanded/
                // collapsed.
                setTimeout(function () {
                    biigle.geo.events.$emit('sidebar.toggle');
                });
            }
        }
    });
});
