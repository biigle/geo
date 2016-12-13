/**
 * A collapsible sidebar that can show different content "tabs"
 *
 * @type {Object}
 */
biigle.geo.components.sidebarTab = {
    template: '<div class="sidebar__tab" :class="classObject"><slot></slot></div>',
    data: function () {
        return {
            open: false
        };
    },
    props: {
        name: {
            type: String,
            required: true
        },
        icon: {
            type: String,
            required: true
        },
        title: {
            type: String
        }
    },
    computed: {
        classObject: function () {
            return {
                'sidebar__tab--open': this.open
            };
        }
    },
    created: function () {
        var self = this;
        this.$parent.$on('open', function (name) {
            self.open = name === self.name;
        });

        this.$parent.$on('close', function () {
            self.open = false;
        });

    }
};
