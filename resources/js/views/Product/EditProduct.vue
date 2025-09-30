<template>
    <div>
        <div class="page-heading">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>{{ __("add_product") }}</h3>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav
                        aria-label="breadcrumb"
                        class="breadcrumb-header float-start float-lg-end"
                    >
                        <ol class="breadcrumb">
                            <!-- Conditionally render breadcrumb item based on the current route -->
                            <li v-if="isSellerRoute" class="breadcrumb-item">
                                <router-link to="/seller/dashboard">{{
                                        __("dashboard")
                                    }}
                                </router-link>
                            </li>
                            <li v-else class="breadcrumb-item">
                                <router-link to="/dashboard">{{
                                        __("dashboard")
                                    }}
                                </router-link>
                            </li>
                            <!-- Conditionally render breadcrumb item based on the current route -->
                            <li v-if="isSellerRoute" class="breadcrumb-item">
                                <router-link to="/seller/manage_products">{{
                                        __("manage_products")
                                    }}
                                </router-link>
                            </li>
                            <li v-else class="breadcrumb-item">
                                <router-link to="/manage_products">{{
                                        __("manage_products")
                                    }}
                                </router-link>
                            </li>

                            <li
                                aria-current="page"
                                class="breadcrumb-item active"
                            >
                                <template v-if="id">
                                    {{ __("edit") }}
                                </template>
                                <template v-else>
                                    {{ __("create") }}
                                </template>
                                {{ __("product") }}
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-md-12 order-md-1 order-last">
                    <form
                        ref="my-form"
                        @submit.prevent="saveRecord"
                        @keydown.enter="$event.preventDefault()"
                    >
                        <div class="card">
                            <div class="card-header">
                                <h4>
                                    <template v-if="id">{{
                                            __("edit")
                                        }}
                                    </template
                                    >
                                    <template v-else>{{
                                            __("create")
                                        }}
                                    </template>
                                    {{ __("product") }}
                                </h4>
                                <span class="pull-right">
                                    <template
                                        v-if="
                                            $roleSeller === login_user.role.name
                                        "
                                    >
                                        <router-link
                                            v-b-tooltip.hover
                                            class="btn btn-primary"
                                            title="Manage Product"
                                            to="/seller/manage_products"
                                        >{{
                                                __("manage_products")
                                            }}</router-link
                                        >
                                    </template>
                                    <template v-else>
                                        <router-link
                                            v-b-tooltip.hover
                                            class="btn btn-primary"
                                            title="Manage Product"
                                            to="/manage_products"
                                        >{{
                                                __("manage_products")
                                            }}</router-link
                                        >
                                    </template>
                                </span>
                            </div>
                            <div class="card-body">
                                <label
                                ><span class="text-danger text-xs">*</span>
                                    {{ __("required_fields") }}</label
                                >
                                <div class="divider">
                                    <div class="divider-text">
                                        {{ __("add_product_form") }}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <label>{{ __("product_name") }}</label>
                                        <i class="text-danger">*</i>
                                        <input
                                            ref="nameInput"
                                            v-model="name"
                                            :placeholder="
                                                __('enter_product_name')
                                            "
                                            class="form-control"
                                            type="text"
                                        />
                                        <p
                                            v-if="validationMessage.name"
                                            class="text-danger"
                                        >
                                            {{ validationMessage.name }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label>{{ __("slug") }}</label>
                                        <i class="text-danger">*</i>
                                        <input
                                            v-model="slug"
                                            :placeholder="
                                                __('enter_product_slug')
                                            "
                                            class="form-control"
                                            type="text"
                                        />
                                    </div>
                                    <template
                                        v-if="
                                            this.$roleSeller ===
                                            login_user.role.name
                                        "
                                    >
                                        <input
                                            v-model="seller_id"
                                            type="hidden"
                                        />
                                    </template>
                                    <template v-else>
                                        <div class="col-md-6">
                                            <label
                                                class="control-label"
                                                for="seller_id"
                                            >{{ __("seller") }}</label
                                            >
                                            <i class="text-danger">*</i>
                                            <select
                                                id="seller_id"
                                                ref="sellerSelect"
                                                v-model="seller_id"
                                                class="form-control form-select"
                                                name="seller_id"
                                                @change="getSellerCategories"
                                            >
                                                <option value="">
                                                    {{ __("select_seller") }}
                                                </option>
                                                <option
                                                    v-for="seller in sellers"
                                                    :value="seller.id"
                                                >
                                                    {{ seller.name }}
                                                </option>
                                            </select>
                                            <p
                                                v-if="validationMessage.seller"
                                                class="text-danger"
                                            >
                                                {{ validationMessage.seller }}
                                            </p>
                                        </div>
                                    </template>
                                    <div class="col-md-6">
                                        <label
                                            class="control-label"
                                            for="tax_id"
                                        >{{ __("tax") }}</label
                                        >
                                        <select
                                            id="tax_id"
                                            v-model="tax_id"
                                            class="form-control form-select"
                                            name="tax_id"
                                        >
                                            <option value="0">
                                                Select Tax
                                            </option>
                                            <option
                                                v-for="tax in taxes"
                                                :value="tax.id"
                                            >
                                                {{ tax.title }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label
                                                class="control-label"
                                                for="tags"
                                            >{{ __("tags") }} (
                                                {{
                                                    __(
                                                        "these_tags_help_you_in_search_result"
                                                    )
                                                }}
                                                )</label
                                            >
                                            <b-form-tags
                                                v-model="tags"
                                                :placeholder="
                                                    __('enter_product_tag')
                                                "
                                                input-id="tags"
                                                no-add-on-enter
                                                separator=" ,;"
                                                tag-variant="primary"
                                            ></b-form-tags>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __("brands") }}</label>
                                            <i class="text-danger">*</i>
                                            <multiselect
                                                ref="brandSelect"
                                                v-model="brand"
                                                :options="brands"
                                                :placeholder="
                                                    __(
                                                        'select_and_search_brands'
                                                    )
                                                "
                                                label="name"
                                                track-by="name"
                                            ></multiselect>
                                            <p
                                                v-if="validationMessage.brands"
                                                class="text-danger"
                                            >
                                                {{ validationMessage.brands }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Deals</label>
                                            <input
                                                v-model="input.deals"
                                                class="form-control"
                                                min="0"
                                                name="deals"
                                                placeholder="enter deals"
                                                step="any"
                                                type="text"
                                            />
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Deal Expiry</label>
                                            <input
                                                v-model="input.deal_expires"
                                                class="form-control"
                                                min="0"
                                                name="deal_expires"
                                                placeholder="enter deals"
                                                step="any"
                                                type="text"
                                            />
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <vue-editor
                                                ref="descriptionEditor"
                                                v-model="description"
                                                :placeholder="
                                                    __(
                                                        'enter_product_description'
                                                    )
                                                "
                                                @blur="validateForm"
                                            ></vue-editor>
                                            <p
                                                v-if="
                                                    validationMessage.description
                                                "
                                                class="text-danger"
                                            >
                                                {{
                                                    validationMessage.description
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label
                                            >{{ __("main_image") }}
                                                <i class="text-danger"
                                                >*</i
                                                ></label
                                            >
                                            <input
                                                ref="file_image"
                                                accept="image/*"
                                                class="file-input"
                                                name="image"
                                                type="file"
                                                @change="fileImage"
                                            />
                                            <p
                                                v-if="validationMessage.image"
                                                class="text-danger"
                                            >
                                                {{ validationMessage.image }}
                                            </p>

                                            <div
                                                class="file-input-div bg-gray-100"
                                                @click="
                                                    $refs.file_image.click()
                                                "
                                                @dragleave="$dragleaveFile"
                                                @dragover="$dragoverFile"
                                                @drop="dropFile"
                                            >
                                                <template
                                                    v-if="
                                                        main_image_name === ''
                                                    "
                                                >
                                                    <label
                                                    ><i
                                                        class="fa fa-cloud-upload fa-2x"
                                                    ></i
                                                    ></label>
                                                    <label>{{
                                                            __(
                                                                "drop_files_here_or_click_to_upload"
                                                            )
                                                        }}</label>
                                                </template>
                                                <template v-else>
                                                    <label
                                                    >Selected file name:-
                                                        {{
                                                            main_image_name
                                                        }}</label
                                                    >
                                                </template>
                                            </div>
                                            <span class="text text-primary">
                                                *Please choose square image of
                                                larger than 350px*350px &amp;
                                                smaller than 550px*550px.</span
                                            >
                                            <p
                                                v-if="mainImageerror"
                                                class="error"
                                            >
                                                {{ mainImageerror }}
                                            </p>

                                            <div
                                                v-if="main_image_path"
                                                class="row"
                                            >
                                                <div class="col-md-4">
                                                    <img
                                                        :src="main_image_path"
                                                        alt="Main Image"
                                                        class="custom-image"
                                                        title="Main Image"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="other_images">{{
                                                    __(
                                                        "other_images_of_the_product"
                                                    )
                                                }}</label>

                                            <input
                                                id="other_images"
                                                ref="file_other_images"
                                                accept="image/*"
                                                class="file-input"
                                                multiple=""
                                                name="other_images[]"
                                                type="file"
                                                v-on:change="otherImage"
                                            />

                                            <div
                                                class="file-input-div bg-gray-100"
                                                @click="
                                                    $refs.file_other_images.click()
                                                "
                                                @dragleave="$dragleaveFile"
                                                @dragover="$dragoverFile"
                                                @drop="dropFileOtherImage"
                                            >
                                                <template
                                                    v-if="images.length === 0"
                                                >
                                                    <label
                                                    ><i
                                                        class="fa fa-cloud-upload fa-2x"
                                                    ></i
                                                    ></label>
                                                    <label>{{
                                                            __(
                                                                "drop_files_here_or_click_to_upload"
                                                            )
                                                        }}</label>
                                                </template>
                                                <template v-else>
                                                    <template
                                                        v-if="
                                                            images.length === 1
                                                        "
                                                    >
                                                        <label
                                                        >Selected file
                                                            name:-
                                                            {{
                                                                images[0].name
                                                            }}</label
                                                        >
                                                    </template>
                                                    <template v-else>
                                                        <label
                                                        >{{
                                                                images.length
                                                            }}
                                                            files
                                                            Selected</label
                                                        >
                                                        <span
                                                        ><small
                                                            v-for="image in images"
                                                        >{{
                                                                image.name
                                                            }},
                                                            </small></span
                                                        >
                                                    </template>
                                                </template>
                                            </div>
                                            <span class="text text-primary">
                                                *Please choose square image of
                                                larger than 350px*350px &amp;
                                                smaller than 550px*550px.</span
                                            >
                                            <p
                                                v-if="otherImageerror"
                                                class="error"
                                            >
                                                {{ otherImageerror }}
                                            </p>

                                            <div
                                                v-if="
                                                    images &&
                                                    images.length !== 0
                                                "
                                                class="row"
                                            >
                                                <h6 class="mt-3">
                                                    Seleted Other Image List.
                                                </h6>
                                                <div
                                                    v-for="image in images"
                                                    v-if="images.length !== 0"
                                                    class="col-md-4 image-container"
                                                >
                                                    <img
                                                        :src="image.url"
                                                        alt="Selected Other Image"
                                                        class="img-thumbnail custom-image"
                                                        title="Selected Other Image"
                                                    />
                                                    <button
                                                        class="btn btn-sm btn-danger btn-remove"
                                                        type="button"
                                                        @click="
                                                            removeOtherImage(
                                                                images.indexOf(
                                                                    image
                                                                )
                                                            )
                                                        "
                                                    >
                                                        <i
                                                            class="fa fa-times-circle"
                                                        ></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div
                                                v-if="
                                                    other_images &&
                                                    other_images.length !== 0
                                                "
                                                class="row"
                                            >
                                                <h6 class="mt-3">
                                                    Uploaded Other Image List.
                                                </h6>
                                                <div
                                                    v-for="(
                                                        image, index
                                                    ) in other_images"
                                                    v-if="
                                                        other_images.length !==
                                                        0
                                                    "
                                                    class="col-md-4 image-container"
                                                >
                                                    <img
                                                        :src="
                                                            $storageUrl +
                                                            image.image
                                                        "
                                                        alt="Other Image"
                                                        class="img-thumbnail custom-image"
                                                        title="Other Image"
                                                    />
                                                    <button
                                                        class="btn btn-sm btn-danger btn-remove"
                                                        type="button"
                                                        @click="
                                                            deleteImage(
                                                                index,
                                                                image.id,
                                                                true
                                                            )
                                                        "
                                                    >
                                                        <i
                                                            class="fa fa-times-circle"
                                                        ></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h4>Product Variant</h4>
                            </div>
                            <div class="card-body">
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="form-group col-6">
                                            <label class="control-label"
                                            >{{ __("stock_limit") }}
                                                <i class="text-danger"
                                                >*</i
                                                ></label
                                            ><br/>
                                            <b-form-radio-group
                                                v-model="is_unlimited_stock"
                                                :options="[
                                                    {
                                                        text: ' Limited',
                                                        value: 0,
                                                    },
                                                    {
                                                        text: ' Unlimited',
                                                        value: 1,
                                                    },
                                                ]"
                                                button-variant="outline-primary"
                                                buttons
                                            ></b-form-radio-group>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-for="(input, k) in inputs"
                                    :key="k"
                                    class="list-group-item"
                                >
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group loose_div">
                                                <label>{{
                                                        __("measurement")
                                                    }}</label>
                                                <i class="text-danger">*</i>

                                                <b-input-group class="mb-2">
                                                    <input
                                                        ref="looseMeasurementInput"
                                                        v-model="
                                                            input.measurement
                                                        "
                                                        class="form-control"
                                                        min="0"
                                                        placeholder="0"
                                                        required
                                                        step="any"
                                                        type="number"
                                                    />
                                                </b-input-group>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group loose_div">
                                                <label
                                                >{{ __("price") }} (
                                                    {{ $currency }}
                                                    ):</label
                                                >
                                                <i class="text-danger">*</i>
                                                <input
                                                    v-model="input.price"
                                                    class="form-control"
                                                    min="0"
                                                    placeholder="0.00"
                                                    required
                                                    type="number"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group loose_div">
                                                <label for="discounted_price"
                                                >{{
                                                        __("discounted_price")
                                                    }}
                                                    (
                                                    {{ $currency }}
                                                    ):</label
                                                >
                                                <input
                                                    id="discounted_price"
                                                    v-model="
                                                        input.discounted_price
                                                    "
                                                    class="form-control"
                                                    min="0"
                                                    placeholder="0.00"
                                                    type="number"
                                                    @input="
                                                        validateDiscountedPriceLoose(
                                                            input
                                                        )
                                                    "
                                                />
                                                <span
                                                    v-if="
                                                        input.validationErrorDiscountedPriceLoose
                                                    "
                                                    class="error"
                                                >{{
                                                        input.validationErrorDiscountedPriceLoose
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Discount Expiry</label>
                                                <input
                                                    v-model="
                                                        input.discounted_expiry
                                                    "
                                                    class="form-control"
                                                    min="0"
                                                    placeholder=""
                                                    type="datetime-local"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group loose_div">
                                                <label>{{
                                                        __("color_code")
                                                    }}</label>
                                                <b-input-group class="mb-2">
                                                    <input
                                                        ref="colorCodeInput"
                                                        v-model="
                                                            input.color_code
                                                        "
                                                        class="form-control"
                                                        min="0"
                                                        placeholder="0"
                                                        step="any"
                                                        type="text"
                                                    />
                                                </b-input-group>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group loose_div">
                                                <label>{{ __("color") }}</label>
                                                <b-input-group class="mb-2">
                                                    <input
                                                        ref="colorInput"
                                                        v-model="input.color"
                                                        class="form-control"
                                                        min="0"
                                                        placeholder="0"
                                                        step="any"
                                                        type="color"
                                                    />
                                                </b-input-group>
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group loose_div">
                                                <label>{{
                                                        __("variant_images")
                                                    }}</label>

                                                <input
                                                    :ref="
                                                        'loose_variant_images_' +
                                                        k
                                                    "
                                                    accept="image/*"
                                                    class="file-input"
                                                    multiple
                                                    type="file"
                                                    @dragleave="$dragleaveFile"
                                                    @dragover="$dragoverFile"
                                                    v-on:change="
                                                        variantImagesChanges(k)
                                                    "
                                                />
                                                <div
                                                    class="file-input-div bg-gray-100"
                                                    @click="
                                                        $refs[
                                                            'loose_variant_images_' +
                                                                k
                                                        ][0].click()
                                                    "
                                                >
                                                    <label
                                                    ><i
                                                        class="fa fa-cloud-upload fa-2x"
                                                    ></i
                                                    ></label>
                                                    <label>{{
                                                            __(
                                                                "drop_files_here_or_click_to_upload"
                                                            )
                                                        }}</label>
                                                </div>

                                                <span class="text text-primary"
                                                >Please choose square image
                                                    of larger than 350px*350px
                                                    &amp; smaller than
                                                    550px*550px.</span
                                                >

                                                <div class="row">
                                                    <div
                                                        v-for="(
                                                            image, index
                                                        ) in input.loose_images"
                                                        v-if="
                                                            input.loose_images
                                                                .length !== 0
                                                        "
                                                        class="col-md-2 image-container"
                                                    >
                                                        <img
                                                            :src="
                                                                $storageUrl +
                                                                image.image
                                                            "
                                                            alt="Variant Image"
                                                            class="img-thumbnail custom-image"
                                                            title="Variant Image"
                                                        />
                                                        <button
                                                            class="btn btn-sm btn-danger btn-remove"
                                                            type="button"
                                                            @click="
                                                                deleteImage(
                                                                    index,
                                                                    image.id,
                                                                    false,
                                                                    k
                                                                )
                                                            "
                                                        >
                                                            <i
                                                                class="fa fa-times-circle"
                                                            ></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div
                                                        v-for="(
                                                            image, index
                                                        ) in variantImages[k]"
                                                        v-if="
                                                            variantImages[k]
                                                                .length !== 0
                                                        "
                                                        class="col-md-4 image-container"
                                                    >
                                                        <img
                                                            :src="image.url"
                                                            alt="Selected Variant Image"
                                                            class="img-thumbnail custom-image"
                                                            title="Selected Variant Image"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            v-if="k === 0"
                                            class="col-md-2 offset-md-10 text-end"
                                        >
                                            <a
                                                v-b-tooltip.hover
                                                class="btn btn-primary"
                                                style="cursor: pointer"
                                                title="Add variant of product"
                                                @click="addRow"
                                            >
                                                <i
                                                    class="fa fa-plus-square"
                                                ></i>
                                                {{ __("add_variant") }}
                                            </a>
                                        </div>
                                        <div
                                            v-if="k !== 0"
                                            class="col-md-2 offset-md-10 text-end"
                                        >
                                            <a
                                                v-b-tooltip.hover
                                                class="btn btn-danger"
                                                style="cursor: pointer"
                                                title="Remove variant of product"
                                                @click="remove(k)"
                                            >
                                                <i class="fa fa-times"></i>
                                                {{ __("remove_variant") }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="row cols-4 p-5">
                                    <div class="col-md-4">
                                        <div
                                            v-if="is_unlimited_stock !== 1"
                                            class="form-group"
                                        >
                                            <label>{{ __("stock") }} </label>
                                            <i class="text-danger">*</i>
                                            <input
                                                v-model="stock"
                                                class="form-control"
                                                min="0"
                                                required
                                                step="any"
                                                type="number"
                                            /><br/>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __("unit") }} </label>
                                            <select
                                                v-model="stock_unit_id"
                                                class="form-control form-select"
                                                name="stock_unit_id"
                                                required
                                            >
                                                <option value="">
                                                    {{ __("select_unit") }}
                                                </option>
                                                <option
                                                    v-for="(unit, key) in units"
                                                    :value="unit.id"
                                                >
                                                    {{ unit.short_code }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __("status") }} </label>
                                            <select
                                                v-model="status"
                                                class="form-control form-select"
                                                name="status"
                                            >
                                                <option value="">
                                                    {{ __("select_status") }}
                                                </option>
                                                <option value="1">
                                                    {{ __("available") }}
                                                </option>
                                                <option value="0">
                                                    {{ __("sold_out") }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h4>{{ __("product_settings") }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label
                                            >{{ __("category") }}
                                                <i class="text-danger"
                                                >*</i
                                                ></label
                                            >

                                            <select
                                                ref="categorySelect"
                                                v-model="category_id"
                                                class="form-control form-select"
                                                v-html="categoryOptions"
                                            ></select>
                                            <p
                                                v-if="
                                                    validationMessage.category
                                                "
                                                class="text-danger"
                                            >
                                                {{ validationMessage.category }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label
                                            >{{ __("product_type") }}
                                            </label>
                                            <select
                                                v-model="product_type"
                                                class="form-control form-select"
                                            >
                                                <option value="">
                                                    {{ __("select_type") }}
                                                </option>
                                                <option value="1">Veg</option>
                                                <option value="2">
                                                    Non Veg
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label
                                            >{{ __("manufacturer") }}
                                            </label>
                                            <input
                                                v-model="manufacturer"
                                                :placeholder="
                                                    __('enter_manufacturer')
                                                "
                                                class="form-control"
                                                type="text"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __("made_in") }}</label>
                                            <multiselect
                                                v-model="made_in"
                                                :options="countries"
                                                :placeholder="
                                                    __(
                                                        'select_and_search_country_name'
                                                    )
                                                "
                                                label="name"
                                                track-by="name"
                                            >
                                                <template
                                                    slot="singleLabel"
                                                    slot-scope="props"
                                                >
                                                    <span class="option__desc">
                                                        <span
                                                            class="option__title"
                                                        >{{
                                                                props.option
                                                                    .name
                                                            }}</span
                                                        >
                                                    </span>
                                                </template>
                                                <template
                                                    slot="option"
                                                    slot-scope="props"
                                                >
                                                    <div class="option__desc">
                                                        <span
                                                            class="option__title"
                                                        >{{
                                                                props.option
                                                                    .name
                                                            }}</span
                                                        >
                                                        <span
                                                            class="option__small"
                                                        >[{{
                                                                props.option
                                                                    .code
                                                            }}]</span
                                                        >
                                                    </div>
                                                </template>
                                            </multiselect>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label for="return_day"
                                                    >FSSAI Lic. No.</label
                                                    >
                                                    <input
                                                        v-model="fssai_lic_no"
                                                        :placeholder="
                                                            __('fssai_lic_no')
                                                        "
                                                        class="form-control"
                                                        type="text"
                                                        @input="
                                                            validateFSSAINumber
                                                        "
                                                    />
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{
                                                            __("is_returnable")
                                                        }}</label
                                                    ><br/>
                                                    <b-form-radio-group
                                                        v-model="return_status"
                                                        :options="[
                                                            {
                                                                text: ' No',
                                                                value: 0,
                                                            },
                                                            {
                                                                text: ' Yes',
                                                                value: 1,
                                                            },
                                                        ]"
                                                        button-variant="outline-primary"
                                                        buttons
                                                    ></b-form-radio-group>
                                                </div>
                                            </div>
                                            <div
                                                v-if="return_status === 1"
                                                id="return_day"
                                                class="col-md-3"
                                            >
                                                <div class="form-group">
                                                    <label for="return_day"
                                                    >{{
                                                            __(
                                                                "max_return_days"
                                                            )
                                                        }}
                                                    </label>
                                                    <input
                                                        v-model="return_days"
                                                        :placeholder="
                                                            __(
                                                                'number_of_days_to_return'
                                                            )
                                                        "
                                                        class="form-control"
                                                        min="0"
                                                        step="any"
                                                        type="number"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label>{{
                                                            __("is_cancelable")
                                                        }}</label
                                                    ><br/>
                                                    <b-form-radio-group
                                                        v-model="
                                                            cancelable_status
                                                        "
                                                        :options="[
                                                            {
                                                                text: ' No',
                                                                value: 0,
                                                            },
                                                            {
                                                                text: ' Yes',
                                                                value: 1,
                                                            },
                                                        ]"
                                                        button-variant="outline-primary"
                                                        buttons
                                                    ></b-form-radio-group>
                                                </div>
                                            </div>
                                            <div
                                                v-if="cancelable_status === 1"
                                                id="till-status"
                                                class="col-md-7"
                                            >
                                                <div class="form-group">
                                                    <label for="till_status"
                                                    >{{
                                                            __(
                                                                "till_which_status"
                                                            )
                                                        }}
                                                    </label>
                                                    <i class="text-danger">*</i>
                                                    <br/>
                                                    <select
                                                        id="till_status"
                                                        v-model="till_status"
                                                        class="form-control form-select"
                                                    >
                                                        <option value="">
                                                            {{
                                                                __(
                                                                    "select_order_statue"
                                                                )
                                                            }}
                                                        </option>
                                                        <option
                                                            v-for="status in order_status"
                                                            :value="status.id"
                                                        >
                                                            {{ status.status }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{
                                                            __("is_cod_allowed")
                                                        }}</label
                                                    ><br/>
                                                    <b-form-radio-group
                                                        v-model="
                                                            cod_allowed_status
                                                        "
                                                        :options="[
                                                            {
                                                                text: ' No',
                                                                value: 0,
                                                            },
                                                            {
                                                                text: ' Yes',
                                                                value: 1,
                                                            },
                                                        ]"
                                                        button-variant="outline-primary"
                                                        buttons
                                                    ></b-form-radio-group>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label
                                                    >{{
                                                            __(
                                                                "tax_included_in_prices"
                                                            )
                                                        }} </label
                                                    ><br/>
                                                    <b-form-radio-group
                                                        v-model="
                                                            tax_included_in_price
                                                        "
                                                        :options="[
                                                            {
                                                                text: ' No',
                                                                value: 0,
                                                            },
                                                            {
                                                                text: ' Yes',
                                                                value: 1,
                                                            },
                                                        ]"
                                                        button-variant="outline-primary"
                                                        buttons
                                                    ></b-form-radio-group>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label
                                                    >{{
                                                            __(
                                                                "total_allowed_quantity"
                                                            )
                                                        }}
                                                    </label>
                                                    <input
                                                        v-model="
                                                            max_allowed_quantity
                                                        "
                                                        class="form-control"
                                                        min="0"
                                                        type="number"
                                                    />
                                                    <span
                                                        class="text text-primary"
                                                    >{{
                                                            __(
                                                                "keep_blank_if_no_such_limit"
                                                            )
                                                        }}</span
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <template
                                            v-if="
                                                this.$roleSeller ===
                                                login_user.role.name
                                            "
                                        >
                                            <input
                                                v-model="is_approved"
                                                type="hidden"
                                                value="0"
                                            />
                                        </template>
                                        <template v-else>
                                            <div class="form-group">
                                                <label class="control-label">{{
                                                        __("product_status")
                                                    }}</label
                                                ><br/>
                                                <div
                                                    id="status"
                                                    class="btn-group"
                                                >
                                                    <label
                                                        class="btn btn-primary"
                                                        data-toggle-class="btn-primary"
                                                        data-toggle-passive-class="btn-default"
                                                    >
                                                        <input
                                                            v-model="
                                                                is_approved
                                                            "
                                                            type="radio"
                                                            value="1"
                                                        />
                                                        Approved
                                                    </label>
                                                    <label
                                                        class="btn btn-danger"
                                                        data-toggle-class="btn-danger"
                                                        data-toggle-passive-class="btn-default"
                                                    >
                                                        <input
                                                            v-model="
                                                                is_approved
                                                            "
                                                            type="radio"
                                                            value="2"
                                                        />
                                                        Not-Approved
                                                    </label>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <b-button
                                    :disabled="isLoading"
                                    type="submit"
                                    variant="primary"
                                    @keydown.enter.native="saveRecord"
                                >
                                    {{ __("save") }}
                                    <b-spinner
                                        v-if="isLoading"
                                        small
                                    ></b-spinner>
                                </b-button>
                                <button class="btn btn-danger" type="reset">
                                    {{ __("clear") }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import Vue from "vue";
import InputTag from "vue-input-tag";
import axios from "axios";
import Multiselect from "vue-multiselect";
import Editor from "@tinymce/tinymce-vue";
import {VueEditor} from "vue2-editor";

import Auth from "../../Auth.js";

export default {
    components: {InputTag, Multiselect, editor: Editor, VueEditor},
    watch: {
        name(newValue) {
            // Automatically generate slug from the product name
            this.slug = newValue
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, "") // Remove non-alphanumeric characters except spaces and hyphens
                .replace(/\s+/g, "-") // Replace spaces with hyphens
                .replace(/-+/g, "-"); // Replace multiple hyphens with a single one
        },
    },

    data: function () {
        return {
            login_user: Auth.user,
            isLoading: false,

            name: "",
            slug: "",
            seller_id: 0,
            tags: [],
            brand: null,
            tax_id: 0,
            category_id: "",
            product_type: "",
            manufacturer: "",
            made_in: "",
            fssai_lic_no: "",
            description: "",
            validationMessage: {
                name: "",
                seller: "",
                brands: "",
                description: "",
                image: "",
                price: "",
                status: "",
                category: "",
            },
            return_status: 0,
            return_days: 0,
            cancelable_status: 0,
            till_status: "",
            cod_allowed_status: 1,
            max_allowed_quantity: 0,
            is_approved: 1,
            stock: 0,
            stock_unit_id: "",
            status: 1,
            is_unlimited_stock: 1,
            tax_included_in_price: 0,
            pincode_ids_exc: null,

            sellers: null,
            taxes: null,
            units: [],
            brands: [],
            countries: [],

            categories: null,
            order_status: null,

            inputs: [{name: "", packet_status: "", packet_stock_unit_id: ""}],

            image: null,
            main_image_path: "",
            main_image_name: "",

            deals: "",
            discounted_expiry: "",

            other_images: null,
            images: [],
            variantImages: {},
            id: null,
            record: null,
            categoryOptions: '<option value="">--Select Category--</option>',
            deleteImageIds: [],
            loggedUser: Auth.user,
            isValid: "",
            input: [],
            mainImageerror: null,
            otherImageerror: null,
            variantImageerror: null,
        };
    },
    computed: {
        isSellerRoute() {
            return this.$route.path.startsWith("/seller/");
        },
    },
    created: function () {
        this.id = this.$route.params.id;

        this.getSellers();
        this.getTaxes();
        this.getUnits();
        this.getCountries();
        this.getOrderStatus();

        if (this.$roleSeller === this.login_user.role.name) {
            this.seller_id = this.login_user.seller.id;

            this.is_approved = this.login_user.seller.require_products_approval === 0 ? 1 : 0;

            this.getSellerCategories();
        }

        this.getBrands().then(() => {
            if (this.id) {
                this.getProduct();
            }
        });
    },

    methods: {
        validateForm() {
            let isValid = true;

            // Reset validation messages
            this.validationMessage = {
                name: "",
                seller: "",
                brands: "",
                description: "",
                image: "",
                price: "",
                color_code: "",
                color: "",
                measurement: "",
                status: "",
                category: "",
                unit: "",
                index: null,
            };

            // Validate product name
            if (!this.name || !this.name.trim()) {
                this.validationMessage.name = "Please enter a product name.";
                this.$refs.nameInput.focus();
                isValid = false;
                return isValid;
            }

            // Validate seller
            if (!this.seller_id || this.seller_id === 0) {
                this.validationMessage.seller = "Please select a seller.";
                this.$refs.sellerSelect.focus();
                isValid = false;
                return isValid;
            }

            // Validate brand
            if (!this.brand) {
                this.validationMessage.brands = "Please select a brand.";
                this.$refs.brandSelect.activate();
                isValid = false;
                return isValid;
            }

            // Validate description
            if (!this.description || !this.description.trim()) {
                this.validationMessage.description =
                    "Product description is required.";
                this.$refs.descriptionEditor.$el.scrollIntoView({
                    behavior: "smooth",
                });
                this.$refs.descriptionEditor.$el
                    .querySelector("textarea")
                    .focus();
                isValid = false;
                return isValid;
            }

            // Validate image (only on create)
            if (!this.id && !this.image) {
                this.validationMessage.image = "Please upload an image.";
                this.$nextTick(() => {
                    if (this.$refs.file_image) {
                        this.$refs.file_image.scrollIntoView({
                            behavior: "smooth",
                        });
                    }
                });
                isValid = false;
                return isValid;
            }

            for (const [index, input] of this.inputs.entries()) {
                if (!input.measurement || input.measurement <= 0) {
                    this.validationMessage.looseMeasurement =
                        "Please enter a valid loose measurement.";
                    this.validationMessage.index = index;

                    this.$nextTick(() => {
                        const looseMeasurementRef =
                            this.$refs.looseMeasurementInput;
                        if (
                            looseMeasurementRef &&
                            looseMeasurementRef.length > 0
                        ) {
                            looseMeasurementRef[0].focus();
                        }
                    });

                    isValid = false;
                    return isValid;
                }

                if (!input.price || input.price <= 0) {
                    this.validationMessage.price =
                        "Please enter a valid loose price.";
                    this.validationMessage.index = index;

                    this.$nextTick(() => {
                        const priceRef = this.$refs[`priceInput_${index}`];
                        if (priceRef && priceRef.length > 0) {
                            priceRef[0].focus();
                        }
                    });

                    isValid = false;
                    return isValid;
                }
            }

            // Validate status
            if (!this.status || this.status === "") {
                this.validationMessage.status = "Please select a status.";
                this.$refs.statusSelect.focus();
                isValid = false;
                return isValid;
            }

            // Validate category
            if (!this.category_id || this.category_id === "") {
                this.validationMessage.category = "Please select a category.";
                this.$refs.categorySelect.focus();
                isValid = false;
                return isValid;
            }

            return isValid;
        },

        createSlug() {
            if (this.name !== "") {
                this.slug = this.name
                    .toLowerCase()
                    .replace(/[^\w ]+/g, "")
                    .replace(/ +/g, "-");
            }
        },
        addRow() {
            this.inputs.push({name: ""});
        },
        remove(index) {
            let variant_id = this.inputs[index].id ? this.inputs[index].id : "";
            if (this.id && variant_id !== "") {
                this.$swal
                    .fire({
                        title: "Are you Sure?",
                        text: "You want be able to revert this",
                        confirmButtonText: "Yes, Sure",
                        cancelButtonText: "Cancel",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#37a279",
                        cancelButtonColor: "#d33",
                    })
                    .then((result) => {
                        if (result.value) {
                            let postData = {
                                id: variant_id,
                            };
                            axios
                                .post(
                                    this.$apiUrl + "/products/delete",
                                    postData
                                )
                                .then((response) => {
                                    let data = response.data;
                                    this.inputs.splice(index, 1);
                                    this.showSuccess(data.message);
                                });
                        }
                    });
            } else {
                this.inputs.splice(index, 1);
            }
        },

        dropFile(event) {
            event.preventDefault();
            this.$refs.file_image.files = event.dataTransfer.files;
            this.fileImage(); // Trigger the onChange event manually
            // Clean up
            event.currentTarget.classList.add("bg-gray-100");
            event.currentTarget.classList.remove("bg-green-300");
        },

        fileImage() {
            const file = this.$refs.file_image?.files[0];

            // Debugging logs
            console.log("File Selected:", file);

            // Reset previous error message
            this.mainImageerror = null;

            if (!file) {
                console.error("No file selected");
                return;
            }

            const validTypes = [
                "image/jpeg",
                "image/png",
                "image/jpg",
                "image/gif",
                "image/webp",
            ];
            if (!validTypes.includes(file.type)) {
                this.mainImageerror =
                    "Invalid file type. Please upload a valid image.";
                console.error("Invalid file type");
                return;
            }

            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                this.mainImageerror = "File size exceeds the 2MB limit.";
                console.error("File size too large");
                return;
            }

            this.image = file; // Assign the image
            console.log("Image Assigned:", this.image);

            this.main_image_path = URL.createObjectURL(file);
            this.main_image_name = file.name;
            console.log("Image Path:", this.main_image_path);
        },

        dropFileOtherImage(event) {
            event.preventDefault();
            this.$refs.file_other_images.files = event.dataTransfer.files;
            this.otherImage(); // Trigger the onChange event manually
            // Clean up
            event.currentTarget.classList.add("bg-gray-100");
            event.currentTarget.classList.remove("bg-green-300");
        },
        removeOtherImage(index) {
            this.images.splice(index, 1);
        },

        otherImage() {
            this.images = [];
            const files = this.$refs.file_other_images.files;

            for (let i = 0; i < files.length; i++) {
                let file = files[i];

                // Check if the file is an image (you can extend the list of allowed file types)
                if (!file.type.startsWith("image/")) {
                    this.otherImageerror =
                        "Invalid file type. Please upload a JPEG, PNG, JPG,  GIF or WEBP image.";
                    file = "";
                } else {
                    let image = {};
                    image.url = URL.createObjectURL(file);
                    image.name = file.name;
                    this.images.push(image);
                }
            }
        },

        variantImagesChanges(index) {
            let tempImages = [];
            Vue.set(this.variantImages, index, []);

            for (
                var i = 0;
                i < this.$refs["loose_variant_images_" + index][0].files.length;
                i++
            ) {
                let image = {};
                let file =
                    this.$refs["loose_variant_images_" + index][0].files[i];
                image.url = URL.createObjectURL(file);
                image.name = file.name;
                tempImages.push(image);
                Vue.set(this.variantImages, index, tempImages);
            }
        },

        getSellerCategories() {
            if (this.seller_id !== 0 && this.seller_id !== "") {
                this.isLoading = true;
                let param = {
                    seller_id: this.seller_id,
                };

                axios
                    .get(this.$apiUrl + "/categories/seller_categories", {
                        params: param,
                    })
                    .then((response) => {
                        this.isLoading = false;
                        let data = response.data;
                        this.categoryOptions =
                            `<option value="">--Select Category--</option>` +
                            data;
                    });
            }
        },
        getCategories() {
            this.isLoading = true;
            axios.get(this.$apiUrl + "/categories/options").then((response) => {
                this.isLoading = false;
                let data = response.data;
                this.categoryOptions =
                    `<option value="">--Select Category--</option>` + data;
            });
        },
        getSellers() {
            this.isLoading = true;
            axios.get(this.$apiUrl + "/sellers").then((response) => {
                this.isLoading = false;
                let data = response.data;
                this.sellers = data.data;
            });
        },
        getTaxes() {
            this.isLoading = true;
            axios.get(this.$apiUrl + "/products/taxes").then((response) => {
                this.isLoading = false;
                let data = response.data;
                this.taxes = data.data;
            });
        },
        getUnits() {
            this.isLoading = true;
            axios.get(this.$apiUrl + "/units/get").then((response) => {
                this.isLoading = false;
                let data = response.data;
                this.units = data.data;
            });
        },
        async getBrands() {
            this.isLoading = true;
            try {
                const response = await axios.get(this.$apiUrl + "/products/brands/get");
                this.brands = response.data.data;
            } catch (error) {
                console.error("Failed to load brands:", error);
            } finally {
                this.isLoading = false;
            }
        },
        getCountries() {
            this.isLoading = true;
            axios.get(this.$apiUrl + "/countries").then((response) => {
                this.isLoading = false;
                let data = response.data;
                this.countries = data.data;
            });
        },

        getOrderStatus() {
            this.isLoading = true;
            axios.get(this.$apiUrl + "/order_statuses").then((response) => {
                this.isLoading = false;
                let data = response.data;
                this.order_status = data.data;
            });
        },
        validateFSSAINumber() {
            const fssaiRegex = /^[0-9]{14}$/;

            if (fssaiRegex.test(this.fssai_lic_no)) {
                this.validationMessage = "";
                this.isValid = true;
            } else {
                this.validationMessage = "Invalid FSSAI Number.";
                this.isValid = false;
            }
        },
        validateDiscountedPrice(input) {
            const discountedPrice = parseFloat(input.discounted_price);
            const actualPrice = parseFloat(input.packet_price);
            if (discountedPrice >= actualPrice) {
                input.validationErrorDiscountedPrice =
                    "Discounted Price must be less than Actual Price";
                input.discounted_price = null;
            } else {
                input.validationErrorDiscountedPrice = null;
            }
        },
        validateDiscountedPriceLoose(input) {
            const discountedPrice = parseFloat(input.discounted_price);
            const actualPrice = parseFloat(input.price);
            if (discountedPrice >= actualPrice) {
                input.validationErrorDiscountedPriceLoose =
                    "Discounted Price must be less than Actual Price";
                input.discounted_price = null;
            } else {
                input.validationErrorDiscountedPriceLoose = null;
            }
        },
        getProduct() {
            this.isLoading = true;

            axios
                .get(this.$apiUrl + "/products/edit/" + this.id)
                .then((response) => {
                    let data = response.data;
                    if (data.status === 1) {
                        this.record = data.data;

                        console.log(data);

                        //Fill Data
                        this.name = this.record.name;
                        this.slug = this.record.slug;
                        this.seller_id = this.record.seller_id;
                        this.getSellerCategories();

                        if (this.record.tags) {
                            this.tags = this.record.tags.split(",");
                        }

                        this.tax_id = this.record.tax_id;

                        this.brand = this.brands.find((item) => {
                            return item.id === this.record.brand_id;
                        });

                        this.category_id = this.record.category_id;

                        this.product_type = this.record.indicator ?? "";
                        this.manufacturer = this.record.manufacturer;

                        this.made_in = this.countries.find((item) => {
                            return item.id === this.record.made_in;
                        });

                        this.tax_included_in_price =
                            this.record.tax_included_in_price;

                        this.return_status = this.record.return_status;
                        this.return_days = this.record.return_days;
                        this.cancelable_status = this.record.cancelable_status;

                        this.till_status = this.record.till_status;
                        this.cod_allowed_status = this.record.cod_allowed;
                        this.max_allowed_quantity =
                            this.record.total_allowed_quantity;
                        this.description = this.record.description;
                        this.is_approved = this.record.is_approved;

                        this.status = this.record.status;
                        this.is_unlimited_stock =
                            this.record.is_unlimited_stock;
                        this.main_image_path =
                            this.$storageUrl + this.record.image;
                        this.other_images = this.record.images;
                        this.fssai_lic_no = this.record.fssai_lic_no;

                        let vm = this;

                        let stock = 0;
                        let stock_unit_id = 0;
                        let status = 0;

                        this.inputs = [];
                        this.record.variants.forEach(function (item) {
                            const variantData = {
                                id: item.id ? item.id : "",
                                measurement: item.measurement,
                                price: item.price,
                                discounted_price: item.discounted_price,
                                discounted_expiry: item.discounted_expiry,
                                color: item.color ?? "",
                                color_code: item.color_code ?? "",
                                packet_stock: item.stock,
                                loose_images: item.images,
                            };
                            vm.inputs.push(variantData);
                            stock = item.stock;
                            stock_unit_id = item.stock_unit_id;
                            status = item.status;
                        });
                        this.stock = stock;
                        this.stock_unit_id = stock_unit_id;
                        this.status = status;
                    } else {
                        this.showError(data.message);
                        setTimeout(() => {
                            this.$router.back();
                        }, 1000);
                    }
                })
                .catch((error) => {
                    this.isLoading = false;
                    if (error.request.statusText) {
                        this.showError(error.request.statusText);
                    } else if (error.message) {
                        this.showError(error.message);
                    } else {
                        console.log(error);
                        this.showError("Something went wrong!");
                    }
                });
        },

        saveRecord: function () {
            if (!this.validateForm()) {
                return;
            }
            this.isLoading = true;
            let vm = this;
            let formData = new FormData();
            if (this.id) {
                formData.append("id", this.id);
                formData.append(
                    "deleteImageIds",
                    JSON.stringify(this.deleteImageIds)
                );
            }
            formData.append("name", this.name);
            formData.append("slug", this.slug);
            formData.append("seller_id", this.seller_id);
            formData.append("tags", this.tags);
            formData.append("tax_id", this.tax_id);
            formData.append("brand_id", this.brand.id);
            formData.append("description", this.description);
            formData.append("is_unlimited_stock", this.is_unlimited_stock);
            formData.append("fssai_lic_no", this.fssai_lic_no);

            for (let i = 0; i < this.inputs.length; i++) {
                formData.append(
                    "variant_id[]",
                    this.inputs[i].id ? this.inputs[i].id : ""
                );
                formData.append("measurement[]", this.inputs[i].measurement);
                formData.append(
                    "price[]",
                    this.inputs[i].price !== undefined ? this.inputs[i].price : 0
                );
                formData.append(
                    "discounted_price[]",
                    this.inputs[i].discounted_price !== undefined
                        ? this.inputs[i].discounted_price
                        : 0
                );
                formData.append(
                    "color_code[]",
                    this.inputs[i].color_code !== undefined
                        ? this.inputs[i].color_code
                        : ""
                );
                formData.append(
                    "color[]",
                    this.inputs[i].color !== undefined
                        ? this.inputs[i].color
                        : ""
                );
                formData.append(
                    "packet_stock[]",
                    this.inputs[i].packet_stock !== undefined
                        ? this.inputs[i].packet_stock
                        : 0
                );
                formData.append(
                    "discounted_expiry[]",
                    this.inputs[i].discounted_expiry !== undefined
                        ? this.inputs[i].discounted_expiry
                        : null
                );
                for (
                    var j = 0;
                    j < this.$refs["loose_variant_images_" + i][0].files.length;
                    j++
                ) {
                    let file =
                        this.$refs["loose_variant_images_" + i][0].files[j];
                    formData.append("loose_variant_images_" + i + "[]", file);
                }
            }
            formData.append("stock", this.stock);
            formData.append("stock_unit_id", this.stock_unit_id);
            formData.append("status", this.status);

            formData.append("stock", this.stock !== undefined ? this.stock : 0);
            formData.append(
                "stock_unit_id",
                this.stock_unit_id !== undefined ? this.stock_unit_id : 0
            );
            formData.append(
                "status",
                this.status !== undefined ? this.status : 0
            );

            formData.append("category_id", this.category_id);
            formData.append("product_type", this.product_type);
            formData.append("manufacturer", this.manufacturer);

            formData.append("made_in", this.made_in ? this.made_in.id : 0);

            formData.append("shipping_type", this.shipping_type);

            // TODO: add deals to products or variants
            formData.append("deals", this.deals);
            formData.append("deals_exipery", this.deal_expires);
            formData.append("pincode_ids_exc", this.pincode_ids_exc);

            formData.append("return_status", this.return_status);
            formData.append("return_days", this.return_days);
            formData.append("cancelable_status", this.cancelable_status);
            formData.append("till_status", this.till_status);
            formData.append("cod_allowed_status", this.cod_allowed_status);
            formData.append("max_allowed_quantity", this.max_allowed_quantity);

            formData.append("is_approved", this.is_approved);
            formData.append(
                "tax_included_in_price",
                this.tax_included_in_price
            );
            formData.append("image", this.image);
            // Other Images
            for (
                var i = 0;
                i < this.$refs.file_other_images.files.length;
                i++
            ) {
                let file = this.$refs.file_other_images.files[i];
                formData.append("other_images[]", file);
            }

            let url = this.$apiUrl + "/products/save";
            if (this.id) {
                url = this.$apiUrl + "/products/update";
            }

            axios
                .post(url, formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                })
                .then((res) => {
                    let data = res.data;

                    console.log("response data", res.data);

                    if (data.status === 1) {
                        this.showMessage("success", data.message);
                        setTimeout(function () {
                            vm.$swal.close();
                            vm.isLoading = false;

                            if (vm.loggedUser?.role?.name === "Seller") {
                                vm.$router.push({
                                    path: "/seller/manage_products",
                                });
                            } else {
                                vm.$router.push({path: "/manage_products"});
                            }
                        }, 2000);
                    } else {
                        vm.showError(data.message);
                        vm.isLoading = false;
                    }
                })
                .catch((error) => {
                    vm.isLoading = false;
                    console.log("ERROR", error);
                    this.showError("Something went wrong!");
                });
        },
        deleteImage(index, id, productImage, key = "") {
            this.$swal
                .fire({
                    title: "Are you Sure?",
                    text: "You want be able to revert this",
                    confirmButtonText: "Yes, Sure",
                    cancelButtonText: "Cancel",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#37a279",
                    cancelButtonColor: "#d33",
                })
                .then((result) => {
                    if (result.value) {
                        this.deleteImageIds.push(id);
                        if (productImage) {
                            this.other_images.splice(index, 1);
                        } else {
                            this.inputs[key].loose_images.splice(index, 1);
                        }
                    }
                });
        },
        changeUnits: function () {
        },
    },
};
</script>
<style scoped>
@import "../../../../node_modules/vue-multiselect/dist/vue-multiselect.min.css";
</style>
