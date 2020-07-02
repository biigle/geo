<script>
import {Events} from './import';
import {LabelTrees} from './import';
import {SidebarTab} from './import';
import {Sidebar} from './import';

/**
 * The sidebar of the geo show view
 */
export default {
    data() {
        return {
            labelTrees: [],
        };
    },
    components: {
        sidebar: Sidebar,
        sidebarTab: SidebarTab,
        labelTrees: LabelTrees,
    },
    methods: {
        handleSidebarToggle() {
            // Use nextTick so the event is handled *after* the sidebar expanded/
            // collapsed.
            this.$nextTick(function () {
                Events.$emit('sidebar.toggle');
            });
        },
        handleSelect(label) {
            Events.$emit('label.selected', label);
        },
        handleDeselect(label) {
            Events.$emit('label.deselected', label);
        },
        handleCleared() {
            Events.$emit('label.cleared');
        }
    },
    created() {
        this.labelTrees = biigle.$require('geo.labelTrees');
    },
};
</script>
