<template>
    <span>
        <strong>with<span v-if="rule.negate">out</span></strong>
        <span v-text="name"></span>
        <strong v-if="dataName" v-text="dataName"></strong>
    </span>
</template>

<script>
/**
 * Component for a geo-filter list item
 *
 * @type {Object}
 */
export default {
    props: {
        rule: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            name: this.rule.id,
        };
    },
    methods: {
        createName(ids) {
            let typeForm = ids.length === 1 ? " image" : " images";
            return "".concat("(", ids.length, typeForm, ")");
        },
    },
    computed: {
        dataName() {
            if (this.rule.data) {
                let ids = JSON.parse(this.rule.data);
                return this.createName(ids);
            }

            return '';
        },
    }
};
</script>
