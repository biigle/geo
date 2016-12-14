/**
 * A component that displays a label tree. The labels can be searched and selected.
 *
 * @type {Object}
 */
biigle.$component('geo.components.labelTree', {
    template: '<div class="label-tree">' +
        '<h4 class="label-tree__title" :if="showTitle" v-text="tree.name"></h4>' +
        '<ul class="label-tree__list">' +
            '<label-tree-label :label="label" v-for="label in rootLabels" @select="emitSelect"></label-tree-label>' +
        '</ul>' +
    '</div>',
    components: {
        labelTreeLabel: biigle.$require('geo.components.labelTreeLabel'),
    },
    props: {
        tree: {
            type: Object,
            required: true,
        },
        showTitle: {
            type: Boolean,
            default: true,
        },
        standalone: {
            type: Boolean,
            default: false,
        }
    },
    computed: {
        labels: function () {
            return this.tree.labels;
        },
        labelMap: function () {
            var map = {};
            for (var i = this.labels.length - 1; i >= 0; i--) {
                map[this.labels[i].id] = this.labels[i];
            }

            return map;
        },
        compiledLabels: function () {
            var compiled = {};
            var parent;
            // Create datastructure that maps label IDs to the child labels
            for (var i = this.labels.length - 1; i >= 0; i--) {
                parent = this.labels[i].parent_id;
                if (compiled.hasOwnProperty(parent)) {
                    compiled[parent].push(this.labels[i]);
                } else {
                    compiled[parent] = [this.labels[i]];
                }
            }

            return compiled;
        },
        rootLabels: function () {
            return this.compiledLabels[null];
        }
    },
    methods: {
        hasLabel: function (id) {
            return this.labelMap.hasOwnProperty(id);
        },
        getLabel: function (id) {
            return this.labelMap[id];
        },
        getParents: function (label) {
            var parents = [];
            while (label.parent_id !== null) {
                label = this.getLabel(label.parent_id);
                parents.unshift(label.id);
            }

            return parents;
        },
        emitSelect: function (label) {
            this.$emit('select', label);
        },
        selectLabel: function (label) {
            // The selected label does not nessecarily belong to this label tree since
            // the tree may be displayed in a label-trees component with other trees.
            var i;
            for (i = this.labels.length - 1; i >= 0; i--) {
                this.labels[i].selected = this.labels[i].id === label.id;
            }

            if (this.hasLabel(label.id)) {
                var parents = this.getParents(label);
                for (i = parents.length - 1; i >= 0; i--) {
                    this.getLabel(parents[i]).open = true;
                }
            }
        },
    },
    created: function () {
        // Set the label properties
        var compiled = this.compiledLabels;
        for (i = this.labels.length - 1; i >= 0; i--) {
            if (compiled.hasOwnProperty(this.labels[i].id)) {
                Vue.set(this.labels[i], 'children', compiled[this.labels[i].id]);
            }

            Vue.set(this.labels[i], 'open', false);
            Vue.set(this.labels[i], 'selected', false);
        }

        // The label tree can be used in a label-trees component or as a single label
        // tree. In a label-trees component only one label can be selected in all label
        // trees so the parent handles the event. A single label tree handley the event
        // by itself.
        if (this.standalone) {
            this.$on('select', this.selectLabel);
        } else {
            this.$parent.$on('select', this.selectLabel);
        }
    }
});
