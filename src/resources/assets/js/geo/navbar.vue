<script>
import { Events } from './import.js';

/**
 * Dynamic information in the navbar of the geo show view
 */
export default {
    data() {
        return {
            number: 0,
            loading: false,
        };
    },
    created() {
        this.number = biigle.$require('geo.images').length;

        Events.on('loading.start', () => {
            this.loading = true;
        });

        Events.on('loading.stop', () => {
            this.loading = false;
        });

        Events.on('imageMap.update', (number) => this.number = number);
    },
};
</script>
