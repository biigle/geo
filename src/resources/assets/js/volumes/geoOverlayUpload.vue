<script>
import Api from './api/geoOverlays';
import GeotiffOverlayForm from './components/geotiffOverlayForm';
import WebmapOverlayForm from './components/webmapOverlayForm';
import OverlayTable from './components/overlayTable';
import {EditorMixin} from './import';
import {handleErrorResponse} from './import';
import {LoaderMixin} from './import';
import Tabs from 'uiv/dist/Tabs';
import Tab from 'uiv/dist/Tab';

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
            overlays: [],
        }
    },
    computed: {
        classObject() {
            return {
                'panel-warning panel--editing': this.editing,
            };
        },
        hasOverlays() {
            return this.overlays.length > 0;
        },
    },
    methods: {
        addOverlay(overlay) {
            overlay.isNew = true;
            this.overlays.unshift(overlay);
            this.finishLoading();
        },
        handleRemove(overlay) {
            this.startLoading();
            Api.delete({id: overlay.id})
                .then(() => this.overlayRemoved(overlay))
                .catch(handleErrorResponse)
                .finally(this.finishLoading);
        },
        overlayRemoved(overlay) {
            let overlays = this.overlays;
            for (let i = overlays.length - 1; i >= 0; i--) {
                if (overlays[i].id === overlay.id) {
                    overlays.splice(i, 1);
                    return;
                }
            }
        },
    },
    created() {
        this.overlays = biigle.$require('volumes.geoOverlays');
    },
};
</script>