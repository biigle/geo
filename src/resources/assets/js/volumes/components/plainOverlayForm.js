/**
 * A component for a form to upload a geo overlay in plain format
 *
 * @type {Object}
 */
biigle.$component('geo.volumes.components.plainOverlayForm', {
    mixins: [biigle.$require('core.mixins.loader')],
    data() {
        return {
            selectedFile: null,
            selectedName: '',
            selectedTLLat: '',
            selectedTLLng: '',
            selectedBRLat: '',
            selectedBRLng: '',
            errors: {},
        };
    },
    computed: {
        fileTooBig() {
            return this.selectedFile && this.selectedFile.size > 10000000;
        },
        canSubmit() {
            return this.selectedFile &&
                !this.fileTooBig &&
                this.selectedTLLat !== '' &&
                this.selectedTLLng !== '' &&
                this.selectedBRLat !== '' &&
                this.selectedBRLng !== '' &&
                !this.loading;
        },
    },
    methods: {
        selectFile(e) {
            this.selectedFile = e.target.files[0];
            if (!this.selectedName) {
                this.selectedName = this.selectedFile.name;
            }

            if (this.fileTooBig) {
                this.errors.file = ['The overlay file must not be larger than 10 MByte.'];
            }
        },
        submit() {
            if (!this.canSubmit) {
                return;
            }

            var data = new FormData(this.$refs.form);
            this.$emit('loading-start');
            this.startLoading();
            biigle.$require('api.geoOverlays')
                .savePlain({volume_id: biigle.$require('volumes.id')}, data)
                .then(this.handleSuccess, this.handleError)
                .finally(this.finishLoading);
        },
        handleError(response) {
            if (response.status === 422) {
                this.errors = response.data;
            } else {
                biigle.$require('messages.store').handleErrorResponse(response);
            }

            this.$emit('error');
        },
        hasError(name) {
            return this.errors.hasOwnProperty(name);
        },
        getError(name) {
            return this.hasError(name) ? this.errors[name].join(' ') : '';
        },
        handleSuccess(response) {
            this.$emit('success', response.data);
            this.reset();
        },
        reset() {
            this.selectedFile = null;
            this.selectedName = '';
            this.selectedTLLat = '';
            this.selectedTLLng = '';
            this.selectedBRLat = '';
            this.selectedBRLng = '';
            this.errors = {};
        },
    },
});
