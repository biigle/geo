<template>
  <slot :submitGeoTiff="submitGeoTiff" :uploadGeoTiff="uploadGeoTiff" :error="error" ></slot>
</template>

<script>
import Api from '../api/geoOverlays.js';

export default {
    props: {
        volumeId: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            error: false,
        };
    },
    emits: [
        'success',
        'upload'
    ],
    methods: {
        handleSuccess(response) {
            this.error = false;
            this.$emit('success', response.data);
        },
        handleError(response) {
            let knownError = response.body.errors && (
                response.body.errors.geotiff || 
                response.body.errors.fileExists ||
                response.body.errors.noPCSKEY ||
                response.body.errors.missingModelTiePoints ||
                response.body.errors.missingModelType || 
                response.body.errors.wrongModelType || 
                response.body.errors.affineTransformation ||
                response.body.errors.userDefined ||
                response.body.errors.unDefined ||
                response.body.errors.transformError
            );
            if (knownError) {
                if (Array.isArray(knownError)) {
                    this.error = knownError[0];
                } else {
                    this.error = knownError;
                }
            }
        },
        submitGeoTiff() {
            // Cannot use $refs, ref() or useTemplateRef()
            // since a wrapper component cannot easily access slot content
            // without a parent component.
            document.getElementById('geoTiffInput').click();
        },
        upload(data) {
            return Api.saveGeoTiff({id: this.volumeId}, data);
        },
        uploadGeoTiff(event) {
            this.$emit('upload', true);
            let data = new FormData();
            data.append('geotiff', event.target.files[0]);
            data.append('volumeId', this.volumeId);
            this.upload(data)
                .then(this.handleSuccess, this.handleError)
                .finally(() => this.$emit('upload', false));
        },
    }
}
</script>