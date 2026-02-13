<template>
  <slot :submitWebMap="submitWebMap" :error="error"></slot>
</template>

<script>
import geoApi from '../api/geoOverlays.js';

export default {
  data() {
    return {
      error: false,
      success: false,
    };
  },
  props: {
    volumeId: {
      type: Number,
      required: true,
    },
    overlayCount: {
      type: Number,
      default: 0
    }
  },
  emits: [
    'success',
    'upload'
  ],
  methods: {
    handleSuccess(response) {
      this.error = false;
      this.success = true;
      this.$emit('success', response.data);
    },
    handleError(response) {
      let knownError = response.body.errors && (
        response.body.errors.url ||
        response.body.errors.tooManyLayers ||
        response.body.errors.invalidWMS ||
        response.body.errors.noValidLayer ||
        response.body.errors.uniqueUrl
      );

      if (knownError) {
        this.error = Array.isArray(knownError) ? knownError[0] : knownError;
      } else {
        if (response.status === 422) {
          this.error = "The url or WMS resource is invalid."
        } else {
          this.error = "An unknown error occured. Please retry later."
        }
      }
    },
    submitWebMap(event) {
      this.$emit('upload', true);
      let data = new FormData();

      if (!this.urlIsValid(event.target[0].value)) {
        return;
      }

      data.append('url', event.target[0].value);
      data.append('layer_index', this.overlayCount);
      geoApi.saveWebMap({ id: this.volumeId }, data)
        .then(this.handleSuccess, this.handleError)
        .finally(() => this.$emit('upload', false))
    },
    urlIsValid(link) {
      try {
        new URL(link);
      } catch (e) {
        this.error = "The URL is invalid."
        this.$emit('upload', false);
        return false;
      }
      return true;
    }
  },
};
</script>

<style scoped></style>
