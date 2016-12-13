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
    props: {
        openTab: {
            type: String
        }
    },
    data: function () {
        return {
            open: false,
        };
    },
    computed: {
        classObject: function () {
            return {
                'sidebar--open': this.open
            };
        }
    },
    mounted: function () {
        var self = this;
        this.$on('open', function () {
            this.open = true;
            this.$emit('toggle');
        });

        this.$on('close', function () {
            this.open = false;
            this.$emit('toggle');
        });

        if (this.openTab) {
            this.$emit('open', this.openTab);
        }
    }
};
