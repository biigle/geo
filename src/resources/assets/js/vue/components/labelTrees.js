/**
 * A component that displays a list of label trees.
 *
 * @type {Object}
 */
biigle.$component('geo.components.labelTrees', {
    template: '<div class="label-trees">' +
        '<label-typeahead v-if="typeahead" :labels="labels" @select="handleSelect"></label-typeahead>' +
        '<label-tree :tree="tree" v-for="tree in trees" @select="handleSelect"></label-tree>' +
    '</div>',
    components: {
        labelTypeahead: biigle.$require('geo.components.labelTypeahead'),
        labelTree: biigle.$require('geo.components.labelTree'),
    },
    props: {
        trees: {
            type: Array,
            required: true,
        },
        typeahead: {
            type: Boolean,
            default: true,
        }
    },
    computed: {
        // All labels of all label trees in a flat list.
        labels: function () {
            var labels = [];
            for (var i = this.trees.length - 1; i >= 0; i--) {
                Array.prototype.push.apply(labels, this.trees[i].labels);
            }

            return labels;
        }
    },
    methods: {
        handleSelect: function (label) {
            this.$emit('select', label);
        }
    }
});
