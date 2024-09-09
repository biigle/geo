<script>
/**
 * The plugin component to edit the context-layer appearance.
 *
 * @type {Object}
 */
export default {
    props: {
        settings: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            opacityValue: '1',
        }
    },
    computed: {
        opacity() {
            return parseFloat(this.opacityValue);
        },
        shown() {
            return this.opacity > 0;
        },
    },
    watch: {
        opacity(opacity) {
            if (opacity < 1) {
                this.settings.set('contextLayerOpacity', opacity);
            } else {
                this.settings.delete('contextLayerOpacity');
            }

            // TODO: Implement OL-layer that shows mosaic
            // this.layer.setOpacity(opacity);
        },
    },
    created() {
        console.log("CREATED Plugin");
        console.log(this.opacity);
        
        this.volumeId = biigle.$require('annotations.volumeId');

        if (this.settings.has('contextLayerOpacity')) {
            this.opacityValue = this.settings.get('contextLayerOpacity');
        }
    }
};