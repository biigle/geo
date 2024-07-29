<template>
    <div class="table-responsive">
        <table v-if="overlays.length !== 0" class="table table-sm" v-cloak>
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
    </div>
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

<style scoped>
.table-responsive {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
    overflow-x: scroll;
}
.table {
    width: 100%;
}

th {
    white-space: normal;
    word-wrap: break-word;
}
</style>