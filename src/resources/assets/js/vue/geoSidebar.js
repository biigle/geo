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
        }
    });
});
