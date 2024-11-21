<component :is="plugins.contextLayer" :settings="settings" inline-template>
    <div v-if="overlays.length !== 0" class="sidebar-tab__section">
        <h5 title="Opacity of the context layer">Geo Overlay Opacity (<span v-if="shown" v-text="opacity"></span><span v-else>hidden</span>)</h5>
        <div class="form-group">
            <input type="range" min="0" max="1" step="0.1" v-model="opacityValue">
        </div>
        <button class="layer-button" @click="showLayers = !showLayers" title="Show available geo-overlays">
            <p>Geo Overlays</p> <i class="icon fa fa-chevron-down" :class="{active: showLayers}" style="font-size: 1.5em;"></i>
        </button>
        <collapse v-model="showLayers">
            <div v-if="overlays.length !== 0">
                <div v-for="overlay in overlays" :key="overlay.id">
                    <button :id="overlay.id" :class="{active: overlay.id === activeId}" class="list-group-item custom" v-on:click="toggleActive(overlay.id)">
                        <span class="ellipsis" :title="overlay.name" v-text="overlay.name"></span>
                    </button>
                </div> 
            </div>
        </collapse>
        <button class="btn btn-default" title="Edit the scale of the context layer" v-on:click="toggleEditing" :class="{active:isEditing}"><span class="fa fa-pencil-alt" aria-hidden="true"></span> Edit</button>
        <span>Scale </span>(<span v-text="scale"></span>)
    </div>
</component>