<script>
import Api from './api/geoOverlays.js';
import GeotiffOverlayForm from './components/geotiffOverlayForm.vue';
import WebmapOverlayForm from './components/webmapOverlayForm.vue';
import OverlayTable from './components/overlayTable.vue';
import {handleErrorResponse, LoaderMixin, EditorMixin, Tab, Tabs} from './import.js';

export default {
    mixins: [
        LoaderMixin,
        EditorMixin,
    ],
    components: {
        tabs: Tabs,
        tab: Tab,
        geotiffOverlayForm: GeotiffOverlayForm,
        overlayTable: OverlayTable,
        webmapOverlayForm: WebmapOverlayForm
    },
    data() {
        return {
            geoOverlays: [],
        }
    },
    computed: {
        classObject() {
            return {
                'panel-warning panel--editing': this.editing,
            };
        },
        hasOverlays() {
            return this.geoOverlays.length > 0;
        },
    },
    methods: {
        addOverlay(overlay) {
            overlay.isNew = true;
            // use toSpliced to trigger change
            this.geoOverlays.map((o) => o.isNew = false);
            this.geoOverlays = this.geoOverlays.toSpliced(0, 0, overlay);
            this.finishLoading();
        },
        handleRemove(overlay) {
            this.startLoading();
            Api.deleteGeoOverlay({id: overlay.id})
                .then(() => this.overlayRemoved(overlay))
                .catch(handleErrorResponse)
                .finally(this.finishLoading);
        },
        overlayRemoved(overlay) {            
            for (let i = this.geoOverlays.length - 1; i >= 0; i--) {
                if (this.geoOverlays[i].id === overlay.id) {
                    this.geoOverlays.splice(i, 1);
                    return;
                }
            }
        },
        handleUpload(uploadInProgress) {
            if(uploadInProgress) {
                this.startLoading();
            } else {
                this.finishLoading();
            }
        }
    },
    created() {
        let volumeId = biigle.$require('volumes.volumeId');
        Api.getOverlays({ id: volumeId })
            .then((res) => {
                this.geoOverlays = res.body.geoOverlays;
            }, handleErrorResponse);
    },
};
</script>