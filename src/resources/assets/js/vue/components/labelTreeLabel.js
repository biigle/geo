/**
 * A component that displays a single label of a label tree.
 *
 * @type {Object}
 */
biigle.$component('geo.components.labelTreeLabel', {
    name: 'label-tree-label',
    template: '<li class="label-tree-label cf" :class="classObject">' +
        '<div class="label-tree-label__name" @click.stop="select">' +
            '<span class="label-tree-label__color" :style="colorStyle" @click.stop="toggleOpen"></span>' +
            '<span v-text="label.name"></span>' +
            '<span v-if="showFavourite" class="label-tree-label__favourite" @click.stop="toggleFavourite">' +
                '<span class="glyphicon" :class="favouriteClass" aria-hidden="true" title=""></span>' +
            '</span>' +
        '</div>' +
        '<ul v-if="label.open" class="label-tree__list">' +
            '<label-tree-label :label="label" v-for="label in label.children" @select="emitSelect"></label-tree-label>' +
        '</ul>' +
    '</li>',
    data: function () {
        return {
            favourite: false
        };
    },
    props: {
        label: {
            type: Object,
            required: true,
        },
        showFavourite: {
            type: Boolean,
            required: false,
        }
    },
    computed: {
        classObject: function () {
            return {
                'label-tree-label--selected': this.label.selected,
                'label-tree-label--expandable': this.label.children,
            };
        },
        colorStyle: function () {
            return {
                'background-color': '#' + this.label.color
            };
        },
        favouriteClass: function () {
            return {
                'glyphicon-star-empty': !this.favourite,
                'glyphicon-star': this.favourite,
            };
        }
    },
    methods: {
        select: function () {
            if (!this.label.selected) {
                this.$emit('select', this.label);
                this.label.open = true;
            } else {
                this.label.open = !this.label.open;
            }
        },
        toggleOpen: function () {
            this.label.open = !this.label.open;
        },
        toggleFavourite: function () {
            this.favourite = !this.favourite;
        },
        emitSelect: function (label) {
            // recursively propagate the event upwards
            this.$emit('select', label);
        }
    }
});
