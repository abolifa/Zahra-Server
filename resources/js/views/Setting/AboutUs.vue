<template>
    <div>
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>About Us</h3>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <router-link to="/dashboard">Dashboard</router-link>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    About Us
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <section class="section">
                <form
                    id="about_us_form"
                    method="post"
                    enctype="multipart/form-data"
                    @submit.prevent="saveRecord"
                >
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Update Information</h4>
                            <span class="pull-right">
                                <button
                                    type="button"
                                    class="btn btn-primary btn_refresh"
                                    v-b-tooltip.hover
                                    :title="__('refresh')"
                                    @click="getAboutUs()"
                                >
                                    <i class="fa fa-refresh" aria-hidden="true"></i>
                                </button>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>About Us:</label>
                                <vue-editor
                                    :placeholder="__('Enter About Us content')"
                                    v-model="about.about_us"
                                ></vue-editor>
                            </div>
                        </div>
                        <div class="card-footer">
                            <b-button type="submit" variant="primary" :disabled="isLoading">
                                Update
                                <b-spinner v-if="isLoading" small></b-spinner>
                            </b-button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import { VueEditor } from "vue2-editor";

export default {
    components: { VueEditor },
    data() {
        return {
            isLoading: false,
            about: {
                about_us: "",
            },
            record: null,
        };
    },
    created() {
        this.getAboutUs();
    },
    methods: {
        getAboutUs() {
            this.isLoading = true;
            axios
                .get(this.$apiUrl + "/about_us")
                .then((response) => {
                    if (response.data.data) {
                        this.record = response.data.data;
                        this.about.about_us = this.record.value;
                    }
                    this.isLoading = false;
                })
                .catch((error) => {
                    this.handleError(error);
                    this.isLoading = false;
                });
        },
        saveRecord() {
            const formData = this.about;
            const url = this.$apiUrl + "/about_us/save";
            this.isLoading = true;

            axios
                .post(url, formData)
                .then((res) => {
                    const data = res.data;
                    if (data.status === 1) {
                        this.showMessage("success", data.message);
                        setTimeout(() => {
                            this.$router.push({ path: "/about_us" });
                            this.isLoading = false;
                        }, 100);
                    } else {
                        this.showError(data.message);
                        this.isLoading = false;
                    }
                })
                .catch((error) => {
                    this.handleError(error);
                    this.isLoading = false;
                });
        },
        handleError(error) {
            if (error.request && error.request.statusText) {
                this.showError(error.request.statusText);
            } else if (error.message) {
                this.showError(error.message);
            } else {
                this.showError("Something went wrong!");
            }
        },
    },
};
</script>
