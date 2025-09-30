<template>
    <b-modal
        ref="my-modal"
        :title="modal_title"
        @hidden="$emit('modalClose')"
        scrollable
        no-close-on-backdrop
        no-fade
        static
    >
        <div slot="modal-footer">
            <b-button
                variant="primary"
                @click="$refs['dummy_submit'].click()"
                :disabled="isLoading"
                >{{ __("save") }}
                <b-spinner v-if="isLoading" small label="Spinning"></b-spinner>
            </b-button>
            <b-button variant="secondary" @click="hideModal">{{
                __("cancel")
            }}</b-button>
        </div>
        <form ref="my-form" @submit.prevent="saveRecord">
            <div v-if="error" class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> {{ error }}
            </div>
            <div class="row">
                <div class="form-group">
                    <label>{{ __("parent_category") }}</label>
                    <select
                        class="form-control form-select"
                        v-model="parent_id"
                        required
                    >
                        <option value="0">{{ __("main_category") }}</option>
                        <option
                            v-for="category in categories"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ __("category_name") }}</label>
                    <input
                        type="text"
                        class="form-control"
                        required
                        v-model="name"
                        :placeholder="__('enter_category_name')"
                    />
                </div>
                <div class="form-group">
                    <label>{{ __("category_subtitle") }}</label>
                    <input
                        type="text"
                        class="form-control"
                        required
                        v-model="subtitle"
                        :placeholder="__('enter_category_subtitle')"
                    />
                </div>
                <div class="form-group">
                    <label>{{ __("image") }}</label>
                    <p class="text-muted">
                        Please choose square image of larger than 350px*350px
                        &amp; smaller than 550px*550px.
                    </p>
                    <span v-if="error" class="error">{{ error }}</span>

                    <input
                        type="file"
                        name="category_image"
                        accept="image/*"
                        v-on:change="handleFileUpload"
                        ref="file_image"
                        class="file-input"
                    />
                    <div
                        class="file-input-div bg-gray-100"
                        @click="$refs.file_image.click()"
                        @drop="dropFile"
                        @dragover="$dragoverFile"
                        @dragleave="$dragleaveFile"
                    >
                        <!--                        <label><i class="fa fa-cloud-upload fa-2x"></i></label>
                        <label>{{ __('drop_files_here_or_click_to_upload') }}</label>-->
                        <!--                        <label>Drop Files here or click to upload</label>-->

                        <template v-if="image && image.name !== ''">
                            <label>Selected file name:- {{ image.name }}</label>
                        </template>
                        <template v-else>
                            <label
                                ><i class="fa fa-cloud-upload fa-2x"></i
                            ></label>
                            <label>{{
                                __("drop_files_here_or_click_to_upload")
                            }}</label>
                        </template>
                    </div>
                    <div class="row" v-if="image_url">
                        <div class="col-md-4">
                            <img
                                class="custom-image"
                                :src="image_url"
                                title="Category Image"
                                alt="Category Image"
                            />
                        </div>
                    </div>
                </div>
                <div class="form-group" v-if="id">
                    <label>Status</label>
                    <div class="col-md-9 text-left mt-1">
                        <b-form-radio-group
                            v-model="status"
                            :options="[
                                { text: ' Deactivated', value: 0 },
                                { text: ' Activated', value: 1 },
                            ]"
                            buttons
                            button-variant="outline-primary"
                            required
                        ></b-form-radio-group>
                    </div>
                </div>
            </div>
            <button ref="dummy_submit" style="display: none"></button>
        </form>
    </b-modal>
</template>

<script>
import axios from "axios";

export default {
    props: ["record", "categories"],
    data: function () {
        return {
            isLoading: false,
            image: null,

            id: this.record ? this.record.id : null,
            name: this.record ? this.record.name : null,
            subtitle: this.record ? this.record.subtitle : null,
            image_url: this.record ? this.record.image_url : null,
            status: this.record ? this.record.status : 1,
            parent_id: this.record ? this.record.parent_id : 0,
            error: null,
        };
    },
    computed: {
        modal_title: function () {
            let title = this.id ? __("edit_category") : __("add_category");
            return title;
        },
    },
    methods: {
        showModal() {
            this.$refs["my-modal"].show();
        },
        hideModal() {
            // console.log('hideModal');
            this.$refs["my-modal"].hide();
        },

        dropFile(event) {
            event.preventDefault();
            this.$refs.file_image.files = event.dataTransfer.files;
            this.handleFileUpload(); // Trigger the onChange event manually
            // Clean up
            event.currentTarget.classList.add("bg-gray-100");
            event.currentTarget.classList.remove("bg-green-300");
        },

        handleFileUpload() {
            const file = this.$refs.file_image.files[0];

            // Reset previous error message
            this.error = null;

            // Check if a file was selected
            if (!file) return;

            // Perform image validation
            const validTypes = [
                "image/jpeg",
                "image/png",
                "image/jpg",
                "image/gif",
                "image/webp",
                "image/svg+xml",
            ];
            if (!validTypes.includes(file.type)) {
                this.error =
                    "Invalid file type. Please upload a JPEG, PNG, JPG,  GIF, WEBP or SVG image.";
                return;
            }

            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                this.error =
                    "File size exceeds the maximum allowed limit (2MB).";
                return;
            }

            // Create a URL for the uploaded image and display it
            this.imageUrl = URL.createObjectURL(file);
            this.image = this.$refs.file_image.files[0];
            this.image_url = URL.createObjectURL(this.image);
        },
        saveRecord: function () {
            this.isLoading = true;
            this.error = null; // Clear any previous errors

            let formData = new FormData();
            if (this.id) {
                formData.append("id", this.id);
            }
            formData.append("name", this.name);
            formData.append("subtitle", this.subtitle);
            formData.append("image", this.image);
            formData.append("status", this.status);
            formData.append("parent_id", this.parent_id);

            let url = this.$apiUrl + "/categories/save";
            if (this.id) {
                url = this.$apiUrl + "/categories/update";
            }

            axios
                .post(url, formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                })
                .then((res) => {
                    let data = res.data;
                    if (data.status === 1) {
                        this.$eventBus.$emit("categorySaved", data.message);
                        this.hideModal();
                        this.$router.push({ path: "/manage_categories" });
                    } else {
                        // Display the error inside the modal
                        this.error = data.message || __("something_went_wrong");
                    }
                    this.isLoading = false;
                })
                .catch((error) => {
                    this.isLoading = false;
                    if (error.response && error.response.data.message) {
                        // Capture the server error and display it
                        this.error = error.response.data.message;
                    } else if (error.message) {
                        // Capture general JavaScript errors
                        this.error = error.message;
                    } else {
                        this.error = __("something_went_wrong");
                    }
                });
        },
    },
    mounted() {
        this.showModal();
    },
};
</script>

<style scoped>
.image_preview {
    margin-top: 5px;
}
</style>
