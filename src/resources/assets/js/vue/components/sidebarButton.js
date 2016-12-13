/**
 * A button to open or switch a tab in a sidebar
 *
 * @type {Object}
 */
biigle.geo.components.sidebarButton = {
    template: '<button class="sidebar__button btn btn-default btn-lg" :class="classObject" @click="toggle" :title="title">' +
        '<span v-if="open" class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>' +
        '<span v-else class="glyphicon" :class="iconClass" aria-hidden="true"></span>' +
    '</button>',
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
        iconClass: function () {
            return 'glyphicon-' + this.icon;
        },
        classObject: function () {
            return {
                active: this.open
            };
        }
    },
    methods: {
        toggle: function () {
            this.$parent.$emit('toggle', this.name);
        }
    },
    mounted: function () {
        var self = this;
        this.$parent.$on('open', function (name) {
            self.open = name === self.name;
        });
    }
};
