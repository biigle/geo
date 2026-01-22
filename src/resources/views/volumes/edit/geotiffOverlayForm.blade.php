<div>
    <p>Upload a geo overlay in geoTIFF (.tif) format</p>
    <form class="form" v-on:submit.prevent="submitGeoTiff">
        <div class="form-group">
            <input class="hidden" id="geoTiffInput" type="file" name="file" v-on:change="uploadGeoTiff" accept=".tif,.tiff">
            <button class="btn btn-default" type="submit">Upload geoTIFF</button>
        </div>
    </form>
    <div class="alert alert-danger" v-if="error.length > 0" v-text="error" v-cloak></div>
</div>
