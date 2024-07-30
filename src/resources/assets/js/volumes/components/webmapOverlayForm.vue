<script>
import Api from '../api/geoOverlays';
import {LoaderMixin} from '../import';

export default {
  data() {
    return {
        error: false,
        success: false,
    };
  },
  mixins: [
        LoaderMixin,
    ],
    props: {
        volumeId: {
            type: Number,
            required: true,
        },
    },
  methods: {
    submitWebMap(event) {
    //   console.log(event.target);
    },
  },
};
</script>

<style scoped>

</style>