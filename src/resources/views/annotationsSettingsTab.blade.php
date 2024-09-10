<component :is="plugins.contextLayer" :settings="settings" inline-template>
    <div v-if="overlays.length !== 0" class="sidebar-tab__section">
        <h5 title="Opacity of the context layer">Context Layer (<span v-if="shown" v-text="opacity"></span><span v-else>hidden</span>)</h5>
        <div class="form-group">
            <input type="range" min="0" max="1" step="0.1" v-model="opacityValue">
        </div>
    </div>
</component>