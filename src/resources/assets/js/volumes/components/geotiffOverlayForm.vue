<template>
    <slot :submitGeoTiff="submitGeoTiff" :uploadGeoTiff="uploadGeoTiff" :error="error"></slot>
</template>

<script>
import Api from '../api/geoOverlays.js';

export default {
    props: {
        volumeId: {
            type: Number,
            required: true,
        },
        jobError: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            error: '',
        };
    },
    emits: [
        'success',
        'upload'
    ],
    methods: {
        handleError(response) {
            let knownError = response.body.errors && (
                response.body.errors.geotiff ||
                response.body.errors.fileExists ||
                response.body.errors.invalidColorSpace ||
                response.body.errors.noPCSKEY ||
                response.body.errors.failedTransformation ||
                response.body.errors.missingModelTiePoints ||
                response.body.errors.missingModelType ||
                response.body.errors.wrongModelType ||
                response.body.errors.affineTransformation ||
                response.body.errors.userDefined ||
                response.body.errors.unDefined ||
                response.body.errors.transformError ||
                response.body.errors.failedUpload
            );

            if (knownError) {
                this.error = Array.isArray(knownError) ? knownError[0] : knownError;
            } else {
                if (response.status === 422) {
                    this.error = "The file is invalid. It must be a valid geotiff, use a 'projected' coordinate reference system or the EPSG code 4326."
                } else {
                    this.error = "An unknown error occured. Please retry later."
                }
            }
            this.$emit('upload', false);
        },
        submitGeoTiff() {
            // Cannot use $refs, ref() or useTemplateRef()
            // since a wrapper component cannot easily access slot content
            // without a parent component.
            document.getElementById('geoTiffInput').click();
        },
        upload(data) {
            return Api.saveGeoTiff({ id: this.volumeId }, data);
        },
        uploadGeoTiff(event) {
            this.$emit('upload', true);
            let data = new FormData();
            data.append('geotiff', event.target.files[0]);
            this.upload(data).catch(this.handleError)
        },
    },
    watch: {
        jobError() {
            this.error = this.jobError
        }
    }
}
</script>
