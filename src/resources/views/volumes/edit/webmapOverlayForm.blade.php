<div>
    <p>Embed a geo overlay by providing a web-map-service url.</p>
    <form class="form" method="POST" v-on:submit.prevent="submitWebMap">
        <div class="form-group">
            <div class="row">
                <div class="form-group col-xs-12">
                    <label for="wms_url" class="form-label">
                        URL
                    </label>
                    <input  
                        class="form-control"
                        type="url" 
                        name="wms_url"
                        placeholder="https://maps.org/geoserver/namespace/wms"
                        value="{{ old('url') }}" 
                        required>
                    <!-- <p class="help-block">The base url of the WMS</p> -->
                </div>
            </div>
            <button class="btn btn-default" type="submit">Upload WMS</button>
        </div>
    </form>
    <div class="alert alert-danger" v-if="error" v-text="error" v-cloak></div>
    <div class="alert alert-success" v-if="success" v-cloak>
        The web-map-service was successfully embedded.
    </div>
</div>