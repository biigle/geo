/**
 * The sidebar of the geo show view
 */
biigle.$viewModel('geo-sidebar', function (element) {
    new Vue({
        el: element,
        components: {
            sidebar: biigle.geo.components.sidebar,
            sidebarButton: biigle.geo.components.sidebarButton,
            sidebarTab: biigle.geo.components.sidebarTab,
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
