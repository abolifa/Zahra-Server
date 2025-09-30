<template>
    <div>
        <div class="page-heading">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Manage Themes</h3>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <router-link to="/dashboard">{{ __('dashboard') }}</router-link>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Manage Themes</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-md-12 order-md-1 order-last">
                    <div class="card">
                        <div class="card-header">
                            <h4>Theme List</h4>
                            <span class="pull-right">
                                <button class="btn btn-primary" @click="create_new = true">Add Theme</button>
                            </span>
                        </div>

                        <div class="card-body">
                            <b-row class="mb-2">
                                <b-col md="3" offset-md="8">
                                    <h6 class="box-title">Search</h6>
                                    <b-form-input
                                        id="filter-input"
                                        v-model="filter"
                                        type="search"
                                        placeholder="Search"
                                    ></b-form-input>
                                </b-col>
                                <b-col md="1" class="text-center">
                                    <button
                                        class="btn btn-primary btn_refresh"
                                        v-b-tooltip.hover
                                        :title="__('refresh')"
                                        @click="getThemes()"
                                    >
                                        <i class="fa fa-refresh" aria-hidden="true"></i>
                                    </button>
                                </b-col>
                            </b-row>

                            <div class="table-responsive">
                                <b-table
                                    :items="themes"
                                    :fields="fields"
                                    :current-page="currentPage"
                                    :per-page="perPage"
                                    :filter="filter"
                                    :sort-by.sync="sortBy"
                                    :sort-desc.sync="sortDesc"
                                    :sort-direction="sortDirection"
                                    :busy="isLoading"
                                    stacked="md"
                                    show-empty
                                    small
                                >
                                    <template #table-busy>
                                        <div class="text-center text-black my-2">
                                            <b-spinner class="align-middle"></b-spinner>
                                            <strong>{{ __('loading') }}...</strong>
                                        </div>
                                    </template>

                                    <!-- Color Columns -->
                                    <template #cell(primary_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.primary_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.primary_color }}</span>
                                        </div>
                                    </template>

                                    <template #cell(secondary_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.secondary_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.secondary_color }}</span>
                                        </div>
                                    </template>

                                    <template #cell(button_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.button_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.button_color }}</span>
                                        </div>
                                    </template>

                                    <template #cell(button_text_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.button_text_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.button_text_color }}</span>
                                        </div>
                                    </template>

                                    <template #cell(active_navigation_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.active_navigation_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.active_navigation_color }}</span>
                                        </div>
                                    </template>

                                    <template #cell(inactive_navigation_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.inactive_navigation_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.inactive_navigation_color }}</span>
                                        </div>
                                    </template>

                                    <template #cell(add_cart_button_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.add_cart_button_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.add_cart_button_color }}</span>
                                        </div>
                                    </template>

                                    <template #cell(add_cart_text_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.add_cart_text_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.add_cart_text_color }}</span>
                                        </div>
                                    </template>

                                    <template #cell(add_cart_border_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.add_cart_border_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.add_cart_border_color }}</span>
                                        </div>
                                    </template>

                                    <template #cell(primary_background_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.primary_background_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.primary_background_color }}</span>
                                        </div>
                                    </template>

                                    <template #cell(secondary_background_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.secondary_background_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.secondary_background_color }}</span>
                                        </div>
                                    </template>

                                    <template #cell(link_color)="row">
                                        <div class="d-flex align-items-center">
                                            <div
                                                :style="{
                                                    width: '20px',
                                                    height: '20px',
                                                    backgroundColor: row.item.link_color,
                                                    border: '1px solid #ccc',
                                                    marginRight: '8px',
                                                }"
                                            ></div>
                                            <span>{{ row.item.secondary_background_color }}</span>
                                        </div>
                                    </template>

                                    <!-- Actions -->
                                    <template #cell(actions)="row">
                                        <button
                                            class="btn btn-sm btn-primary"
                                            @click="edit_record = row.item"
                                            v-b-tooltip.hover
                                            :title="__('edit')"
                                        >
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button
                                            class="btn btn-sm btn-danger"
                                            @click="deleteTheme(row.index, row.item.id)"
                                            v-b-tooltip.hover
                                            :title="__('delete')"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </template>
                                </b-table>
                            </div>

                            <b-row>
                                <b-col md="2" class="my-1">
                                    <b-form-group
                                        :label="__('per_page')"
                                        label-for="per-page-select"
                                        label-align-sm="right"
                                        label-size="sm"
                                        class="mb-0"
                                    >
                                        <b-form-select
                                            id="per-page-select"
                                            v-model="perPage"
                                            :options="pageOptions"
                                            size="sm"
                                            class="form-control form-select"
                                        ></b-form-select>
                                    </b-form-group>
                                </b-col>
                                <b-col md="4" class="my-1" offset-md="6">
                                    <b-pagination
                                        v-model="currentPage"
                                        :total-rows="totalRows"
                                        :per-page="perPage"
                                        align="fill"
                                        size="sm"
                                        class="my-0"
                                    ></b-pagination>
                                </b-col>
                            </b-row>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <app-edit-record
            v-if="create_new || edit_record"
            :record="edit_record"
            @modalClose="hideModal"
            @themeSaved="handleThemeSaved"
        />
    </div>
</template>


<script>
import EditRecord from "./Edit.vue";

export default {
    components: {
        "app-edit-record": EditRecord,
    },
    data() {
        return {
            fields: [
                { key: "id", label: "ID", sortable: true },
                {key: "primary_color", label: "primary_color", sortable: true },
                {key: "secondary_color", label: "secondary_color", sortable: true },
                {key: "button_color", label: "button_color", sortable: true },
                {key: "button_text_color", label: "button_text_color", sortable: true },
                {key: "active_navigation_color", label: "active_navigation_color", sortable: true },
                {key: "inactive_navigation_color", label: "inactive_navigation_color", sortable: true },
                {key: "add_cart_button_color", label: "add_cart_button_color", sortable: true },
                {key: "add_cart_text_color", label: "add_cart_text_color", sortable: true },
                {key: "add_cart_border_color", label: "add_cart_border_color", sortable: true },
                {key: "primary_background_color", label: "primary_background_color", sortable: true },
                {key: "secondary_background_color", label: "secondary_background_color", sortable: true },
                {key: "link_color", label: "link_color", sortable: true },
                { key: "actions", label: "Actions" },

            ],
            totalRows: 1,
            currentPage: 1,
            perPage: 10,
            pageOptions: [5, 10, 15],
            sortBy: "",
            sortDesc: false,
            filter: null,
            themes: [],
            isLoading: false,
            create_new: false,
            edit_record: null,
        };
    },
    methods: {
        getThemes() {
            this.isLoading = true;
            axios.get("/api/themes").then((response) => {
                this.isLoading = false;
                this.themes = response.data;
                this.totalRows = this.themes.length;
            });
        },
        handleThemeSaved() {
            // Refresh the themes list after a theme is saved
            this.getThemes();

            // Optionally show a success toast
            this.$bvToast.toast("Themes refreshed successfully!", {
                variant: "success",
                solid: true,
            });
        },
        deleteTheme(index, id) {
            this.$swal
                .fire({
                    title: "Are you Sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Delete it!",
                    cancelButtonText: "Cancel",
                    confirmButtonColor: "#37a279",
                    cancelButtonColor: "#d33",
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        this.isLoading = true;
                        axios
                            .delete(`/api/themes/${id}`)
                            .then(() => {
                                this.themes.splice(index, 1);
                                this.isLoading = false;
                                this.$swal.fire({
                                    title: "Deleted!",
                                    text: "The theme has been deleted.",
                                    icon: "success",
                                    confirmButtonColor: "#37a279",
                                });
                            })
                            .catch((error) => {
                                this.isLoading = false;
                                this.$swal.fire({
                                    title: "Error!",
                                    text: error.response?.data?.message || "Something went wrong.",
                                    icon: "error",
                                    confirmButtonColor: "#d33",
                                });
                            });
                    }
                });
        },
        hideModal() {
            this.create_new = false;
            this.edit_record = null;
        },
    },
    mounted() {
        this.getThemes();
    },
};
</script>
