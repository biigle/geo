<div>
    <p>Upload a geo overlay in geoTIFF (.tif) format</p>
    <form class="form" v-on:submit.prevent="submitGeoTiff">
        <div class="form-group">
            <input class="hidden" ref="geoTiffInput" type="file" name="file" v-on:change="uploadGeoTiff" accept=".tif">
            <button class="btn btn-default" type="submit" :disabled="loading">Upload geoTIFF</button>
        </div>
    </form>
</div>