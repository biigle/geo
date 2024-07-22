<template>
    <table class="table table-sm" v-cloak>
        <thead>
            <tr>
                <th></th>
                <th>#</th>
                <th>Filename</th>
                <th>Browsing</th>
                <th>Context</th>
                <th>Delete</th>
            </tr>
        </thead>
        <draggable v-model="sortedOverlays" tag="tbody" handle=".handle">
            <tr is="overlay-item" v-for="(overlay, idx) in sortedOverlays" :key="overlay.id" :index="idx" :overlay="overlay" :volume-id="volumeId" v-on:remove="$emit('remove', overlay);">
            </tr>
        </draggable>
    </table>
</template>

<script>
import OverlayItem from './overlayItem';
import draggable from 'vuedraggable';




export default {
    data() {
        return {
            sortedOverlays: [],
        };
    },
    props: {
        overlays: {
            type: Array,
            required: true,
        },
        volumeId: {
            type: Number,
            required: true,
        }
    },
    mounted() {
        this.sortedOverlays = JSON.parse(JSON.stringify(this.overlays));
    },
    components: {
        overlayItem: OverlayItem,
        draggable,
    }
};
</script>