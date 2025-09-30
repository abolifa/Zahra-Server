<template>
    <b-modal
        ref="my-modal"
        :title="modal_title"
        no-close-on-backdrop
        no-fade
        scrollable
        size="lg"
        static
        @hidden="$emit('modalClose')"
    >
        <div slot="modal-footer">
            <b-button
                :disabled="isLoading"
                variant="primary"
                @click="$refs['dummy_submit'].click()"
            >
                Save
                <b-spinner v-if="isLoading" label="Spinning" small></b-spinner>
            </b-button>
            <b-button variant="secondary" @click="hideModal">Cancel</b-button>
        </div>
        <form ref="my-form" @submit.prevent="saveRecord">
            <div class="row">
                <label
                ><span class="text-danger text-xs">*</span>
                    {{ __("required_fields") }}</label
                >
                <div class="divider mt-0">
                    <div class="divider-text">Theme Form</div>
                </div>
                <div class="form-group col-md-6">
                    <label>Primary Color <i class="text-danger">*</i></label>
                    <input
                        v-model="primary_color"
                        class="form-control"
                        placeholder="Enter primary color"
                        required
                        type="color"
                    />
                </div>
                <div class="form-group col-md-6">
                    <label>Secondary Color <i class="text-danger">*</i></label>
                    <input
                        v-model="secondary_color"
                        class="form-control"
                        placeholder="Enter secondary color"
                        required
                        type="color"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label>Tertiary Color <i class="text-danger">*</i></label>
                    <input
                        v-model="tertiary_color"
                        class="form-control"
                        placeholder="Enter tertiary color"
                        required
                        type="color"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label>Button Color <i class="text-danger">*</i></label>
                    <input
                        v-model="button_color"
                        class="form-control"
                        placeholder="Enter Button color"
                        required
                        type="color"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label
                    >Button Text Color <i class="text-danger">*</i></label
                    >
                    <input
                        v-model="button_text_color"
                        class="form-control"
                        placeholder="Enter Button Text color"
                        required
                        type="color"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label
                    >Active Navigation Color
                        <i class="text-danger">*</i></label
                    >
                    <input
                        v-model="active_navigation_color"
                        class="form-control"
                        placeholder="Enter Active Navigation color"
                        required
                        type="color"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label
                    >Inactive Navigation Color
                        <i class="text-danger">*</i></label
                    >
                    <input
                        v-model="inactive_navigation_color"
                        class="form-control"
                        placeholder="Enter Inactive Navigation color"
                        required
                        type="color"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label
                    >Add Cart Button Color
                        <i class="text-danger">*</i></label
                    >
                    <input
                        v-model="add_cart_button_color"
                        class="form-control"
                        placeholder="Enter Add cart Button color"
                        required
                        type="color"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label
                    >Add Cart Text Color <i class="text-danger">*</i></label
                    >
                    <input
                        v-model="add_cart_text_color"
                        class="form-control"
                        placeholder="Enter Add Cart text color"
                        required
                        type="color"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label
                    >Add Cart Border Color
                        <i class="text-danger">*</i></label
                    >
                    <input
                        v-model="add_cart_border_color"
                        class="form-control"
                        placeholder="Add Cart Border color"
                        required
                        type="color"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label
                    >Primary Background Color
                        <i class="text-danger">*</i></label
                    >
                    <input
                        v-model="primary_background_color"
                        class="form-control"
                        placeholder="Add Primary Background color"
                        required
                        type="color"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label
                    >Secondary Background Color
                        <i class="text-danger">*</i></label
                    >
                    <input
                        v-model="secondary_background_color"
                        class="form-control"
                        placeholder="Add secondary Background color"
                        required
                        type="color"
                        width="120px"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label
                    >Third Background Color
                        <i class="text-danger">*</i></label
                    >
                    <input
                        v-model="third_background_color"
                        class="form-control"
                        placeholder="Add third Background color"
                        required
                        type="color"
                        width="120px"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label
                    >Fourth Background Color
                        <i class="text-danger">*</i></label
                    >
                    <input
                        v-model="fourth_background_color"
                        class="form-control"
                        placeholder="Add Fourth Background color"
                        required
                        type="color"
                        width="120px"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label
                    >Fifth Background Color
                        <i class="text-danger">*</i></label
                    >
                    <input
                        v-model="fifth_background_color"
                        class="form-control"
                        placeholder="Add fifth Background color"
                        required
                        type="color"
                        width="120px"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label>Link Color <i class="text-danger">*</i></label>
                    <input
                        v-model="link_color"
                        class="form-control"
                        placeholder="Add Link color"
                        required
                        type="color"
                        width="120px"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label>Color 6 <i class="text-danger">*</i></label>
                    <input
                        v-model="color_6"
                        class="form-control"
                        placeholder="Add Color 6 color"
                        required
                        type="color"
                        width="120px"
                    />
                </div>

                <div class="form-group col-md-6">
                    <label>Color 7 <i class="text-danger">*</i></label>
                    <input
                        v-model="color_7"
                        class="form-control"
                        placeholder="Add Color 7 color"
                        required
                        type="color"
                        width="120px"
                    />
                </div>

                <div class="form-group col-md-12">
                    <label>Status</label>
                    <b-form-radio-group
                        v-model="status"
                        :options="[
      { text: 'Inactive', value: 0 },
      { text: 'Active', value: 1 },
    ]"
                        button-variant="outline-primary"
                        buttons
                        class="d-block custom-radio-spacing"
                    />
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
            id: this.record ? this.record.id : "",
            primary_color: this.record ? this.record.primary_color : "#ffffff",
            secondary_color: this.record
                ? this.record.secondary_color
                : "#ffffff",
            tertiary_color: this.record ? this.record.tertiary_color : "#ffffff",
            button_color: this.record ? this.record.button_color : "#ffffff",
            button_text_color: this.record
                ? this.record.button_text_color
                : "#ffffff",
            active_navigation_color: this.record
                ? this.record.active_navigation_color
                : "#ffffff",
            inactive_navigation_color: this.record
                ? this.record.inactive_navigation_color
                : "#ffffff",
            add_cart_button_color: this.record
                ? this.record.add_cart_button_color
                : "#ffffff",
            add_cart_text_color: this.record
                ? this.record.add_cart_text_color
                : "#ffffff",
            add_cart_border_color: this.record
                ? this.record.add_cart_border_color
                : "#ffffff",
            primary_background_color: this.record
                ? this.record.primary_background_color
                : "#ffffff",
            secondary_background_color: this.record
                ? this.record.secondary_background_color
                : "#ffffff",
            third_background_color: this.record
                ? this.record.third_background_color
                : "#ffffff",
            fourth_background_color: this.record
                ? this.record.fourth_background_color
                : "#ffffff",
            fifth_background_color: this.record
                ? this.record.fifth_background_color
                : "#ffffff",
            link_color: this.record ? this.record.link_color : "#ffffff",
            color_6: this.record ? this.record.color_6 : "#ffffff",
            color_7: this.record ? this.record.color_7 : "#ffffff",
            status: this.record?.status !== undefined ? Number(this.record.status) : 1,

        };
    },
    computed: {
        modal_title: function () {
            return this.id ? "Edit Theme" : "Add Theme";
        },
    },
    methods: {
        showModal() {
            this.$refs["my-modal"].show();
        },
        hideModal() {
            this.$refs["my-modal"].hide();
        },

        saveRecord: function () {
            this.isLoading = true;
            const payload = {
                primary_color: this.primary_color,
                secondary_color: this.secondary_color,
                tertiary_color: this.tertiary_color,
                button_color: this.button_color,
                button_text_color: this.button_text_color,
                active_navigation_color: this.active_navigation_color,
                inactive_navigation_color: this.inactive_navigation_color,
                add_cart_button_color: this.add_cart_button_color,
                add_cart_text_color: this.add_cart_text_color,
                add_cart_border_color: this.add_cart_border_color,
                primary_background_color: this.primary_background_color,
                secondary_background_color: this.secondary_background_color,
                third_background_color: this.third_background_color,
                fourth_background_color: this.fourth_background_color,
                fifth_background_color: this.fifth_background_color,
                link_color: this.link_color,
                color_6: this.color_6,
                color_7: this.color_7,
                status: this.status === 1 || this.status === true,
            };

            let url = `/api/themes`;

            console.log("payload =>", payload);
            console.log("id =>", this.id);
            if (this.id) {
                url = `/api/themes/${this.id}`;
                axios
                    .put(url, payload)
                    .then(() => {
                        this.$emit("themeSaved");
                        this.$bvToast.toast("Theme updated successfully!", {
                            variant: "success",
                            solid: true,
                        });
                        this.hideModal();
                    })
                    .catch((error) => {
                        this.isLoading = false;
                        console.error(
                            error.response ? error.response.data : error.message
                        );
                    });
            } else {
                axios
                    .post(url, payload)
                    .then(() => {
                        this.$emit("themeSaved"); // Emit the event
                        this.$bvToast.toast("Theme added successfully!", {
                            variant: "success",
                            solid: true,
                        });
                        this.hideModal();
                    })
                    .catch((error) => {
                        this.isLoading = false;
                        console.error(error);
                    });
            }
        },
    },
    mounted() {
        console.log("Record Props:", this.record);
        this.showModal();
    },
};
</script>

<style scoped>
.custom-radio-spacing .btn {
    margin-right: 10px !important; /* Add spacing between buttons */
    padding-left: 15px !important; /* Add padding to the left of the label */
    padding-right: 15px !important; /* Add padding to the right of the label */
}
</style>
