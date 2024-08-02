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
            geotiffOverlays: [],
            webmapOverlays: [],
        }
    },
    computed: {
        classObject() {
            return {
                'panel-warning panel--editing': this.editing,
            };
        },
    },
    methods: {
        addOverlay(overlay) {
            overlay.isNew = true;
            this.geotiffOverlays.unshift(overlay);
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
            let overlays = this.geotiffOverlays;
            for (let i = overlays.length - 1; i >= 0; i--) {
                if (overlays[i].id === overlay.id) {
                    overlays.splice(i, 1);
                    return;
                }
            }
        },
        // dataKey either 'geotiffOverlays' or 'webmapOverlays'
        hasOverlays(dataKey) {
            return this[dataKey].length > 0;
        },
    },
    created() {
        this.geotiffOverlays = biigle.$require('volumes.geoOverlays');
        this.webmapOverlays = biigle.$require('volumes.webmapOverlays');
    },
};
</script>