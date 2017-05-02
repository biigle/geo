<form v-on:submit.prevent="submit" enctype="multipart/form-data" ref="form">
    <div class="row">
        <div class="col-xs-6 form-group" :class="{'has-error': hasError('file')}">
            <label for="plainOverlayFile">Overlay file</label>
            <input id="plainOverlayFile" type="file" title="Overlay file" accept="image/jpeg,image/png,image/tiff" name="file" v-on:change="selectFile" required/>
            <p v-if="hasError('file')" v-cloak class="help-block" v-text="getError('file')"></p>
        </div>
        <div class="col-xs-6 form-group" :class="{'has-error': hasError('name')}">
            <label for="plainOverlayName">Overlay name</label>
            <input id="plainOverlayName" class="form-control" placeholder="My new overlay" type="text" name="name" title="Overlay name" v-model="selectedName"/>
            <p v-if="hasError('name')" v-cloak class="help-block" v-text="getError('name')"></p>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-6 form-group" :class="{'has-error': hasError('top_left_lat')}">
            <label for="plainOverlayTLLat">Top left latitude</label>
            <input id="plainOverlayTLLat" class="form-control" placeholder="52.03737667" type="number" name="top_left_lat" step="any" min="-90" max="90" title="Top left latitude" v-model="selectedTLLat"/>
            <p v-if="hasError('top_left_lat')" v-cloak class="help-block" v-text="getError('top_left_lat')"></p>
        </div>
        <div class="col-xs-6 form-group" :class="{'has-error': hasError('top_left_lng')}">
            <label for="plainOverlayTLLng">Top left longitude</label>
            <input id="plainOverlayTLLng" class="form-control" placeholder="8.49285457" type="number" name="top_left_lng" step="any" min="-180" max="180" title="Top left longitude" v-model="selectedTLLng"/>
            <p v-if="hasError('top_left_lng')" v-cloak class="help-block" v-text="getError('top_left_lng')"></p>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-6 form-group" :class="{'has-error': hasError('bottom_right_lat')}">
            <label for="plainOverlayBRLat">Bottom right latitude</label>
            <input id="plainOverlayBRLat" class="form-control" placeholder="52.03737667" type="number" name="bottom_right_lat" step="any" min="-90" max="90" title="Bottom right latitude" v-model="selectedBRLat"/>
            <p v-if="hasError('bottom_right_lat')" v-cloak class="help-block" v-text="getError('bottom_right_lat')"></p>
        </div>
        <div class="col-xs-6 form-group" :class="{'has-error': hasError('bottom_right_lng')}">
            <label for="plainOverlayBRLng">Bottom right longitude</label>
            <input id="plainOverlayBRLng" class="form-control" placeholder="8.4931067" type="number" name="bottom_right_lng" step="any" min="-180" max="180" title="Bottom right longitude" v-model="selectedBRLng"/>
            <p v-if="hasError('bottom_right_lng')" v-cloak class="help-block" v-text="getError('bottom_right_lng')"></p>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <p class="help-block">Latitude and longitude coordinates must be given in Spherical Mercator.</p>
            <button type="submit" class="btn btn-success" title="Upload the new geo overlay" :disabled="!canSubmit">Submit</button>
        </div>
    </div>
</form>
