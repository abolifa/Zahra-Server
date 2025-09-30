<template>
    <b-modal
        ref="my-modal"
        :title="modal_title"
        @hidden="$emit('modalClose')"
        scrollable
        no-close-on-backdrop
        no-fade
        size="lg"
        static
    >
        <div slot="modal-footer">
            <b-button
                variant="primary"
                @click="$refs['dummy_submit'].click()"
                :disabled="isLoading"
                >Save
                <b-spinner v-if="isLoading" small></b-spinner>
            </b-button>
            <b-button variant="secondary" @click="hideModal">Cancel</b-button>
        </div>
        <form ref="my-form" @submit.prevent="saveRecord">
            <div class="row">
                <div class="form-group">
                    <label for="offer_image"
                        >Offer Image <i class="text-danger">*</i></label
                    >
                    <!-- Show Image Error -->
                    <span
                        v-if="imageError"
                        class="alert alert-danger d-block p-2 mb-2"
                    >
                        <i class="bi bi-exclamation-triangle"></i>
                        {{ imageError }}
                    </span>
                    <input
                        type="file"
                        name="offer_image"
                        accept="image/*"
                        id="offer_image"
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
                        <div class="col-md-12">
                            <img
                                class="custom-image"
                                :src="image_url"
                                title="Offer Image"
                                alt="Offer Image"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="exampleInputEmail1">Categories</label>
                <select
                    class="form-control form-select"
                    v-model="category_id"
                    v-html="categoryOptions"
                ></select>
            </div>

            <div class="form-group">
                <label for="exampleInputEmail1">Discount</label>
                <input
                    type="number"
                    v-model="discount"
                    name="discount"
                    class="form-control"
                    id="exampleInputEmail1"
                    aria-describedby="emailHelp"
                    required
                />
            </div>

            <div class="form-group">
                <label for="exampleFormControlSelect1">Brands</label>
                <select
                    class="form-control"
                    id="category"
                    v-model="brand_id"
                >
                    <option value="">Select</option>
                    <option
                        v-for="brand in brands"
                        :key="brand.id"
                        :value="brand.id"
                    >
                        {{ brand.name }}
                    </option>
                </select>
            </div>


            <div class="form-group">
                <label for="position"
                    >Position <i class="text-danger">*</i></label
                >
                <select
                    name="position"
                    id="position"
                    v-model="position"
                    class="form-control form-select"
                >
                    <option value="top">Top</option>
                    <option value="below_slider">Below Slider</option>
                    <option value="below_category">Below Category</option>
                    <option value="below_section">Below Section</option>
                </select>
            </div>

            <div class="form-group" v-if="position === 'below_section'">
                <label for="section_id"
                    >Section Position <i class="text-danger">*</i></label
                >
                <select
                    name="section_id"
                    id="section_id"
                    v-model="section_id"
                    class="form-control form-select"
                >
                    <option value="">Select Section</option>
                    <option v-for="section in sections" :value="section.id">
                        {{ section.title }}
                    </option>
                </select>
            </div>

            <button ref="dummy_submit" style="display: none"></button>
        </form>
    </b-modal>
</template>

<script>
import axios from "axios";

export default {
    props: ["record", "sections"],

    data: function () {
        return {
            isLoading: false,
            id: this.record ? this.record.id : null,
            image: this.record ? this.record.image : "",
            image_url: this.record ? this.record.image : "",
            position: this.record ? this.record.position : "top",
            section_id: this.record ? this.record.section_id : null,
            brand_id: this.record ? this.record.brand_id : null,
            category_id: this.record ? this.record.category_id : null,
            discount: this.record ? this.record.discount : "",
            error: null,
            imageError: null,
            brands: [],
            selectedBrands: "",
            categoryOptions: '<option value="">--Select Category--</option>',
        };
    },
    watch: {
        record: {
            immediate: true,
            handler(newRecord) {
                if (newRecord) {
                    this.image_url = newRecord.image
                        ? `${this.$storageUrl}${newRecord.image}` // Ensure full URL
                        : "";
                    this.showModal();
                }
            },
        },
    },
    computed: {
        modal_title: function () {
            let title = this.id ? "Edit" : "Add";
            title += " New Offers Images here";
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
            ];
            if (!validTypes.includes(file.type)) {
                this.error =
                    "Invalid file type. Please upload a JPEG, PNG, JPG, GIF, or WEBP image.";
                return;
            }

            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                this.error =
                    "File size exceeds the maximum allowed limit (2MB).";
                return;
            }

            // Create a URL for the uploaded image and display it
            this.image = file;
            this.image_url = URL.createObjectURL(file);
        },

        saveRecord: function () {
            this.imageError = null; // Reset the error message

            // Validate the image
            if (!this.image) {
                this.imageError = "Please upload an image.";
                return; // Stop the form submission
            }

            this.isLoading = true;
            let formData = new FormData();

            if (this.id) {
                formData.append("id", this.id);
            }
            formData.append("image", this.image);
            formData.append("position", this.position);
            formData.append("section_id", this.section_id);
            formData.append("brand_id", this.brand_id);
            formData.append("category_id", this.category_id);
            formData.append("discount", this.discount);

            let url = this.$apiUrl + "/offers/save";
            if (this.id) {
                url = this.$apiUrl + "/offers/update";
            }

            axios
                .post(url, formData)
                .then((res) => {
                    let data = res.data;
                    if (data.status === 1) {
                        this.$eventBus.$emit("offerSaved", data.message);
                        this.$router.push({ path: "/offers" });
                        this.hideModal();
                    } else {
                        this.showError(data.message);
                        this.isLoading = false;
                    }
                })
                .catch((error) => {
                    this.isLoading = false;
                    if (
                        error.response &&
                        error.response.data &&
                        error.response.data.message
                    ) {
                        this.imageError = error.response.data.message;
                    } else if (error.message) {
                        this.showError(error.message);
                    } else {
                        this.showError(__("something_went_wrong"));
                    }
                });
        },

        fetchCategories() {
            axios
                .get(this.$apiUrl + "/categories/options")
                .then((response) => {
                    this.categories = response.data;
                    this.categoryOptions =
                        `<option value="">--Select Category--</option>` +
                        response.data;
                    console.log("categories :" + this.categories);
                })
                .catch((error) => {
                    console.error("Error fetching categories:", error);
                });
        },
        getBrands() {
            this.isLoading = true;
            axios
                .get(this.$apiUrl + "/products/brands/get")
                .then((response) => {
                    this.isLoading = false;
                    let data = response.data;
                    this.brands = data.data;
                    console.log("brands : ", this.brands);
                });
        },
    },
    mounted() {
        this.showModal();
        this.fetchCategories();
        this.getBrands();
    },
};
</script>

<style scoped>
/*.image_preview{
    margin-top: 5px;
}*/
</style>
