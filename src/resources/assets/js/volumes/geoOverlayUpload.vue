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
            geoOverlays: [],
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
            this.geoOverlays.unshift(overlay);
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
        // dataKey either 'geotiffOverlays' or 'webmapOverlays'
        hasOverlays(dataKey) {
            return this[dataKey].length > 0;
        },
    },
    created() {
        this.geoOverlays = biigle.$require('volumes.geoOverlays');
    },
};
</script>