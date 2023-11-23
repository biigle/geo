<template>
    <div>
        
    </div>
</template>

<script>
import Api from './api/geoOverlays';
// import PlainOverlayForm from './components/plainOverlayForm';
import {EditorMixin} from './import';
import {handleErrorResponse} from './import';
import {LoaderMixin} from './import';
import Tabs from 'uiv/dist/tabs';
import Tab from 'uiv/dist/tab';


/**
 * The geo overlay upload of the volume edit page.
 */

let overlayItem = {
    props: ['overlay'],
    computed: {
        classObject() {
            return {
                'list-group-item-success': this.overlay.isNew,
            };
        },
        title() {
            return 'Delete overlay ' + this.overlay.name;
        },
    },
    methods: {
        remove() {
            if (confirm(`Are you sure you want to delete the overlay ${this.overlay.name}?`)) {
                this.$emit('remove', this.overlay);
            }
        },
    },
};

export default {
    mixins: [
        LoaderMixin,
        EditorMixin,
    ],
    components: {
        tabs: Tabs,
        tab: Tab,
        overlayItem: overlayItem,
        // plainOverlayForm: PlainOverlayForm,
    },
    data: {
        overlays: [],
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