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
            volumeId: null,
            overlays: null,
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
        this.volumeId = biigle.$require('annotations.volumeId');
        this.overlays = biigle.$require('annotations.overlays');

        // check if there are context-overlays
        if(this.overlays.length !== 0) {
            // check if an opacity preference is available in settings and change it in case
            if (this.settings.has('contextLayerOpacity')) {
                this.opacityValue = this.settings.get('contextLayerOpacity');
            }
        } else {
            // if no context-overlays available
        }
    }
};
</script>