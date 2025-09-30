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
            >
                {{ __("save") }}
                <b-spinner v-if="isLoading" small label="Spinning"></b-spinner>
            </b-button>
            <b-button variant="secondary" @click="hideModal">
                {{ __("cancel") }}
            </b-button>
        </div>
        <form ref="my-form" @submit.prevent="saveRecord">
            <div class="row">
                <div class="form-group">
                    <label>{{ __("name") }}</label>
                    <input
                        type="text"
                        class="form-control"
                        required
                        v-model="name"
                        :placeholder="__('enter_name')"
                    />
                </div>

                <!-- Primary Image Upload -->
                <div class="form-group">
                    <label for="image">{{ __("brand_logo") }}</label>
                    <p class="text-muted">
                        Please choose a square image of larger than 350px*350px
                        &amp; smaller than 550px*550px.
                    </p>
                    <span v-if="error.image" class="error text-danger">{{
                        error.image
                    }}</span>
                    <input
                        type="file"
                        name="image"
                        id="image"
                        accept="image/*"
                        v-on:change="handleFileUpload('image')"
                        ref="file_image"
                        class="file-input"
                    />
                    <div
                        class="file-input-div bg-gray-100"
                        @click="$refs.file_image.click()"
                        @drop="dropFile('image', $event)"
                        @dragover="$dragoverFile"
                        @dragleave="$dragleaveFile"
                    >
                        <template v-if="image && imageUrl">
                            <!-- Display preview for primary image -->
                            <img
                                :src="imageUrl"
                                alt="Brand Logo"
                                class="img-fluid overflow-hidden"
                                style="max-width: 200px; max-height: 200px"
                            />
                            <label class="text-sm"
                                >Selected file name:
                                {{ image.name || "Existing Image" }}</label
                            >
                        </template>
                        <template v-else>
                            <label>
                                <i class="fa fa-cloud-upload fa-2x"></i>
                            </label>
                            <label>{{
                                __("drop_files_here_or_click_to_upload")
                            }}</label>
                        </template>
                    </div>
                </div>

                <!-- Cover Image Upload -->
                <div class="form-group">
                    <label for="cover_image">{{ __("cover_image") }}</label>
                    <p class="text-muted">
                        Please choose a rectangular image of larger than
                        700px*400px.
                    </p>
                    <span v-if="error.coverImage" class="error text-danger">{{
                        error.coverImage
                    }}</span>
                    <input
                        type="file"
                        name="cover_image"
                        id="cover_image"
                        accept="image/*"
                        v-on:change="handleFileUpload('coverImage')"
                        ref="file_cover_image"
                        class="file-input"
                    />
                    <div
                        class="file-input-div bg-gray-100"
                        @click="$refs.file_cover_image.click()"
                        @drop="dropFile('coverImage', $event)"
                        @dragover="$dragoverFile"
                        @dragleave="$dragleaveFile"
                    >
                        <template v-if="coverImage && coverImageUrl">
                            <!-- Display preview for cover image -->
                            <img
                                :src="coverImageUrl"
                                alt="Cover Image"
                                class="img-fluid overflow-hidden"
                                style="max-width: 200px; max-height: 200px"
                            />
                            <label class="text-sm"
                                >Selected file name:
                                {{
                                    coverImage.name || "Existing Cover Image"
                                }}</label
                            >
                        </template>
                        <template v-else>
                            <label>
                                <i class="fa fa-cloud-upload fa-2x"></i>
                            </label>
                            <label>{{
                                __("drop_files_here_or_click_to_upload")
                            }}</label>
                        </template>
                    </div>
                </div>

                <div class="form-group" v-if="id">
                    <label>{{ __("status") }}</label>
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
            <button ref="dummy_submit" style="display: none"></button>
        </form>
    </b-modal>
</template>

<script>
import axios from "axios";

export default {
    props: ["record"],
    data: function () {
        return {
            isLoading: false,
            image: "",
            coverImage: "",
            id: this.record ? this.record.id : null,
            name: this.record ? this.record.name : null,
            image_url: this.record ? this.record.image_url : "",
            cover_image_url: this.record ? this.record.cover_image : "",
            status: this.record ? this.record.status : 1,
            error: { image: null, coverImage: null },
        };
    },
    computed: {
        modal_title: function () {
            let title = this.id ? __("edit_brand") : __("add_brand");
            return title;
        },
    },
    methods: {
        showModal() {
            this.$refs["my-modal"].show();
        },
        hideModal() {
            this.$refs["my-modal"].hide();
        },
        dropFile(field, event) {
            event.preventDefault();

            const files = event.dataTransfer.files;

            // Ensure files exist
            if (!files || files.length === 0) {
                console.error("No files found in drop event.");
                return;
            }

            // Assign files to the respective input element
            if (field === "image") {
                this.$refs.file_image.files = files;
            } else if (field === "coverImage") {
                this.$refs.file_cover_image.files = files;
            } else {
                console.error(`Unknown field: ${field}`);
                return;
            }

            // Trigger the file upload handler
            this.handleFileUpload(field);

            // Reset styles for drag-and-drop
            event.currentTarget.classList.add("bg-gray-100");
            event.currentTarget.classList.remove("bg-green-300");
        },
        handleFileUpload(field) {
            const fileInput =
                field === "image"
                    ? this.$refs.file_image
                    : this.$refs.file_cover_image;
            const file = fileInput?.files[0];

            // Reset error message
            this.error[field] = null;

            // Check if a file was selected
            if (!file) {
                console.error("No file selected.");
                return;
            }

            // Validate file type and size
            const validTypes = [
                "image/jpeg",
                "image/png",
                "image/jpg",
                "image/gif",
                "image/webp",
            ];
            if (!validTypes.includes(file.type)) {
                this.error[field] =
                    "Invalid file type. Please upload an image file.";
                return;
            }

            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                this.error[field] =
                    "File size exceeds the maximum limit of 2MB.";
                return;
            }

            // Create and assign a preview URL for the image
            this[`${field}Url`] = URL.createObjectURL(file);
            this[field] = file;
        },
        saveRecord: function () {
            this.error = { image: null, coverImage: null };

            if (!this.image && !this.image_url) {
                this.error.image = "Please select an image before saving.";
                return;
            }

            if (!this.name || this.name.trim() === "") {
                this.error.image = "Name is required.";
                return;
            }

            this.isLoading = true;

            let formData = new FormData();
            if (this.id) {
                formData.append("id", this.id);
            }
            formData.append("name", this.name);

            // Check if a new image file is uploaded
            if (this.image && typeof this.image !== "string") {
                formData.append("image", this.image);
            } else if (this.image_url) {
                formData.append("image_url", this.image_url); // Send existing image URL if no new image is uploaded
            }

            // Check if a new cover image file is uploaded
            if (this.coverImage && typeof this.coverImage !== "string") {
                formData.append("cover_image", this.coverImage);
            } else if (this.cover_image_url) {
                formData.append("cover_image_url", this.cover_image_url); // Send existing cover image URL if no new image is uploaded
            }

            formData.append("status", this.status);

            for (let [key, value] of formData.entries()) {
                console.log(`${key}:`, value);
            }

            const url = this.id
                ? `${this.$apiUrl}/products/brands/update`
                : `${this.$apiUrl}/products/brands/save`;

            axios
                .post(url, formData)
                .then((res) => {
                    console.log(res.data);
                    const data = res.data;
                    if (data.status === 1) {
                        this.$eventBus.$emit("recordSaved", data.message);
                        this.hideModal();
                    } else {
                        this.showError(data.message);
                    }
                    this.isLoading = false;
                })
                .catch((error) => {
                    console.log(error);
                    this.isLoading = false;
                    this.error.image =
                        error.response?.data?.message ||
                        "Something went wrong!";
                });
        },
    },
    mounted() {
        this.showModal();

        console.log("Record data:", this.record);

        if (this.record) {
            this.image = this.record.image_url ? this.record.image_url : null;
            this.coverImage = this.record.cover_image_url
                ? this.record.cover_image_url
                : null; // Use cover_image_url
            this.imageUrl = this.image;
            this.coverImageUrl = this.coverImage; // Correctly set coverImageUrl
        }
    },
};
</script>
