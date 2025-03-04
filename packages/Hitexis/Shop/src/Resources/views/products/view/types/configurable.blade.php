@if (Hitexis\Product\Helpers\ProductType::hasVariants($product->type))
    {!! view_render_event('bagisto.shop.products.view.configurable-options.before', ['product' => $product]) !!}

    <v-product-configurable-options :errors="errors" @color-change="updateVariantColor"></v-product-configurable-options>

    {!! view_render_event('bagisto.shop.products.view.configurable-options.after', ['product' => $product]) !!}

    @push('scripts')
        <script
            type="text/x-template"
            id="v-product-configurable-options-template"
        >
            <div class="w-[455px] max-w-full">
                <input
                    type="hidden"
                    name="selected_configurable_option"
                    id="selected_configurable_option"
                    :value="selectedOptionVariant"
                    ref="selected_configurable_option"
                >

                <div
                    class="mt-5"
                    v-for='(attribute, index) in childAttributes'
                >
                    <!-- Dropdown Label -->
                    <h2 class="mb-4 text-xl max-sm:text-base">
                        @{{ attribute.label }}
                    </h2>

                    <!-- Dropdown Options Container -->
                    <template
                        v-if="! attribute.swatch_type || attribute.swatch_type == '' || attribute.swatch_type == 'dropdown'"
                    >
                        <!-- Dropdown Options -->
                        <v-field
                            as="select"
                            :name="'super_attribute[' + attribute.id + ']'"
                            class="custom-select mb-3 block w-full cursor-pointer rounded-lg border border-zinc-200 bg-white px-5 py-3 text-base text-zinc-500 focus:border-blue-500 focus:ring-blue-500 max-md:w-[110px] max-md:border-0 max-md:outline-none"
                            :class="[errors['super_attribute[' + attribute.id + ']'] ? 'border border-red-500' : '']"
                            :id="'attribute_' + attribute.id"
                            v-model="attribute.selectedValue"
                            rules="required"
                            :label="attribute.label"
                            :aria-label="attribute.label"
                            :disabled="attribute.disabled"
                            @change="configure(attribute, $event.target.value)"
                        >
                            <option
                                v-for='(option, index) in attribute.options'
                                :value="option.id"
                            >
                                @{{ option.label }}
                            </option>
                        </v-field>
                    </template>

                    <!-- Swatch Options Container -->
                    <template v-else>
                        <!-- Swatch Options -->
                        <div class="flex items-center gap-3">
                            <template v-for="(option, index) in attribute.options">
                                <template v-if="option.id">
                                    <!-- Color Swatch Options -->
                                    <label
                                        class="relative -m-0.5 flex cursor-pointer items-center justify-center rounded-full p-0.5 focus:outline-none"
                                        :class="{'ring-2 ring-gray-900' : option.id == attribute.selectedValue}"
                                        :style="{ '--tw-ring-color': option.swatch_value }"
                                        :title="option.label"
                                        v-if="attribute.swatch_type == 'color'"
                                    >
                                        <v-field
                                            type="radio"
                                            :name="'super_attribute[' + attribute.id + ']'"
                                            :value="option.id"
                                            v-slot="{ field }"
                                            rules="required"
                                            :label="attribute.label"
                                            :aria-label="attribute.label"
                                        >
                                            <input
                                                type="radio"
                                                :name="'super_attribute[' + attribute.id + ']'"
                                                :value="option.id"
                                                v-bind="field"
                                                :id="'attribute_' + attribute.id"
                                                :aria-labelledby="'color-choice-' + index + '-label'"
                                                class="peer sr-only"
                                                @click="configure(attribute, $event.target.value)"
                                            />
                                        </v-field>

                                        <span
                                            class="h-8 w-8 rounded-full border border-opacity-10 max-sm:h-[25px] max-sm:w-[25px]"
                                            :style="{ 'background-color': option.swatch_value, 'border-color': option.swatch_value}"
                                        ></span>
                                    </label>

                                    <!-- Image Swatch Options -->
                                    <label 
                                        class="group relative flex h-[60px] w-[60px] cursor-pointer items-center justify-center overflow-hidden rounded-md border bg-white font-medium uppercase text-gray-900 shadow-sm hover:bg-gray-50 max-sm:h-[35px] max-sm:w-[35px] sm:py-6"
                                        :class="{'border-navyBlue' : option.id == attribute.selectedValue }"
                                        :title="option.label"
                                        v-if="attribute.swatch_type == 'image'"
                                    >    
                                        <v-field
                                            type="radio"
                                            :name="'super_attribute[' + attribute.id + ']'"
                                            v-model="attribute.selectedValue"
                                            :value="option.id"
                                            v-slot="{ field }"
                                            rules="required"
                                            :label="attribute.label"
                                            :aria-label="attribute.label"
                                        >
                                            <input
                                                type="radio"
                                                :name="'super_attribute[' + attribute.id + ']'"
                                                :value="option.id"
                                                v-bind="field"
                                                :id="'attribute_' + attribute.id"
                                                :aria-labelledby="'color-choice-' + index + '-label'"
                                                class="peer sr-only"
                                                @click="configure(attribute, $event.target.value)"
                                            />
                                        </v-field>

                                        <img
                                            :src="option.swatch_value"
                                            :title="option.label"
                                        />
                                    </label>

                                    <!-- Text Swatch Options -->
                                    <label 
                                        class="group relative flex h-[60px] min-w-[60px] cursor-pointer items-center justify-center rounded-full border border-gray-300 bg-white px-4 py-3 font-medium uppercase text-gray-900 shadow-sm hover:bg-gray-50 max-sm:h-[35px] max-sm:w-[35px] sm:py-6"
                                        :class="{'border-transparent !bg-navyBlue text-white' : option.id == attribute.selectedValue }"
                                        :title="option.label"
                                        v-if="attribute.swatch_type == 'text'"
                                    >
                                        <v-field
                                            type="radio"
                                            :name="'super_attribute[' + attribute.id + ']'"
                                            :value="option.id"
                                            v-model="attribute.selectedValue"
                                            v-slot="{ field }"
                                            rules="required"
                                            :label="attribute.label"
                                            :aria-label="attribute.label"
                                        >
                                            <input
                                                type="radio"
                                                :name="'super_attribute[' + attribute.id + ']'"
                                                :value="option.id"
                                                v-bind="field"
                                                :id="'attribute_' + attribute.id"
                                                class="peer sr-only"
                                                :aria-labelledby="'color-choice-' + index + '-label'"
                                                @click="configure(attribute, $event.target.value)"
                                            />
                                        </v-field>

                                        <span class="text-lg max-sm:text-sm">
                                            @{{ option.label }}
                                        </span>

                                        <span class="pointer-events-none absolute -inset-px rounded-full"></span>
                                    </label>
                                </template>
                            </template>

                            <span
                                class="text-sm text-gray-600 max-sm:text-xs"
                                v-if="! attribute.options.length"
                            >
                                @lang('shop::app.products.view.type.configurable.select-above-options')
                            </span>
                        </div>
                    </template>

                    <v-error-message
                        :name="'super_attribute[' + attribute.id + ']'"
                        v-slot="{ message }"
                    >
                        <p class="mt-1 text-xs italic text-red-500">
                            @{{ message }}
                        </p>
                    </v-error-message>
                </div>
            </div>
            <input name='variant-id' type='hidden' v-model="selectedOptionVariant" />
        </script>

        <script type="module">
            let galleryImages = @json(product_image()->getGalleryImages($product));

            app.component('v-product-configurable-options', {
                template: '#v-product-configurable-options-template',

                props: ['errors'],

                data() {
                    return {
                        config: @json(app('Hitexis\Product\Helpers\ConfigurableOption')->getConfigurationConfig($product)),
                        childAttributes: [],
                        possibleOptionVariant: null,
                        selectedOptionVariant: '',
                        galleryImages: [],
                    };
                },

                mounted() {
                    let attributes = JSON.parse(JSON.stringify(this.config)).attributes.slice();
                    let index = attributes.length;

                    while (index--) {
                        let attribute = attributes[index];
                        attribute.options = [];

                        if (index) {
                            attribute.disabled = true;
                        } else {
                            this.fillAttributeOptions(attribute);
                        }

                        attribute = Object.assign(attribute, {
                            childAttributes: this.childAttributes.slice(),
                            prevAttribute: attributes[index - 1],
                            nextAttribute: attributes[index + 1]
                        });

                        this.childAttributes.unshift(attribute);
                    }
                },

                methods: {
                    configure(attribute, optionId) {
                        attribute.selectedValue = optionId;

                        let selectedAttributes = {};
                        this.childAttributes.forEach(attr => {
                            if (attr.selectedValue) {
                                selectedAttributes[attr.code] = attr.selectedValue;
                            }
                        });

                        // Emit the color change event if the attribute is color
                        if (attribute.label.toLowerCase().includes('color')) {
                            this.$emit('color-change', attribute.selectedValue);
                        }

                        this.possibleOptionVariant = this.getPossibleOptionVariant(selectedAttributes);

                        if (attribute.nextAttribute) {
                            attribute.nextAttribute.disabled = false;
                            this.clearAttributeSelection(attribute.nextAttribute);
                            this.fillAttributeOptions(attribute.nextAttribute);
                            this.resetChildAttributes(attribute.nextAttribute);
                        } else {
                            this.selectedOptionVariant = this.possibleOptionVariant;
                            EventBus.$emit('variant-id-updated', this.selectedOptionVariant);

                        }

                        this.getSku(@json($product->id), attribute);
                        this.reloadPrice();
                        this.reloadImages();
                    },
                    getPossibleOptionVariant(selectedAttributes) {
                        // Ensure variants data is present
                        if (!this.config.index) {
                            console.warn("Variants data is not available:", this.config.index);
                            return null;
                        }

                        // Preserve the original indexes
                        const variants = Object.entries(this.config.index);

                        // Hardcoded attribute IDs
                        const colorAttributeId = 23;
                        const sizeAttributeId = 24;
                        
                        // Loop through all variants and check if all selected attributes match
                        for (let [index, variant] of variants) {
                            let isMatch = false;
                            const variantArray = Object.entries(variant);
                            
                            if (selectedAttributes.color && selectedAttributes.size &&
                                variantArray[0][1] == selectedAttributes.color &&
                                variantArray[1][1] == selectedAttributes.size
                            ) {
                                isMatch = true;
                            }

                            // Check if color matches
                            if (
                                selectedAttributes.color && selectedAttributes.size == undefined &&
                                variantArray[0][1] == selectedAttributes.color
                            ) {
                                isMatch = true;
                            }

                            // Check if size matches
                            if (
                                selectedAttributes.size && selectedAttributes.color == undefined &&
                                variantArray[0][1] == selectedAttributes.size
                            ) {
                                isMatch = true;
                            }


                            if (isMatch) {
                                this.selectedOptionVariant = index
                                return index; // Return the index of the matching variant as a string
                            }
                        }

                        
                        return null; // Return null if no matching variant is found
                    },


                    fillAttributeOptions(attribute) {
                        let options = this.config.attributes.find(
                            tempAttribute => tempAttribute.id === attribute.id
                        )?.options;

                        attribute.options = [
                            {
                                id: '',
                                label: "@lang('shop::app.products.view.type.configurable.select-options')",
                                products: [],
                            },
                        ];

                        if (!options) {
                            return;
                        }

                        let prevAttributeSelectedOption = attribute.prevAttribute?.options.find(
                            option => option.id == attribute.prevAttribute.selectedValue
                        );

                        let index = 1;

                        for (let i = 0; i < options.length; i++) {
                            let allowedProducts = [];

                            if (prevAttributeSelectedOption) {
                                for (let j = 0; j < options[i].products.length; j++) {
                                    if (
                                        prevAttributeSelectedOption.allowedProducts &&
                                        prevAttributeSelectedOption.allowedProducts.includes(
                                            options[i].products[j]
                                        )
                                    ) {
                                        allowedProducts.push(options[i].products[j]);
                                    }
                                }
                            } else {
                                allowedProducts = options[i].products.slice(0);
                            }

                            if (allowedProducts.length > 0) {
                                options[i].allowedProducts = allowedProducts;
                                attribute.options[index++] = options[i];
                            }
                        }
                    },

                    resetChildAttributes(attribute) {
                        if (!attribute.childAttributes) {
                            return;
                        }

                        attribute.childAttributes.forEach(function (set) {
                            set.selectedValue = null;
                            set.disabled = true;
                        });
                    },

                    clearAttributeSelection(attribute) {
                        if (!attribute) {
                            return;
                        }

                        attribute.selectedValue = null;
                        this.selectedOptionVariant = null;
                    },

                    reloadPrice() {
                        let selectedOptionCount = this.childAttributes.filter(
                            attribute => attribute.selectedValue
                        ).length;

                        if (this.childAttributes.length === selectedOptionCount) {
                            document.querySelector('.price-label').style.display = 'none';

                            document.querySelector('.final-price').innerHTML =
                                this.config.variant_prices[this.possibleOptionVariant].final.formatted_price;

                            this.$emitter.emit(
                                'configurable-variant-selected-event',
                                this.possibleOptionVariant
                            );
                        } else {
                            document.querySelector('.price-label').style.display = 'inline-block';

                            document.querySelector('.final-price').innerHTML =
                                this.config.regular.formatted_price;

                            this.$emitter.emit('configurable-variant-selected-event', 0);
                        }
                    },

                    reloadImages() {
                        galleryImages.splice(0, galleryImages.length);

                        if (this.possibleOptionVariant) {
                            this.config.variant_images[this.possibleOptionVariant].forEach(function (image) {
                                galleryImages.push(image);
                            });

                            this.config.variant_videos[this.possibleOptionVariant].forEach(function (video) {
                                galleryImages.push(video);
                            });
                        }

                        this.galleryImages.forEach(function (image) {
                            galleryImages.push(image);
                        });

                        if (galleryImages.length) {
                            this.$parent.$parent.$refs.gallery.media.images = [...galleryImages];
                        }

                        this.$emitter.emit(
                            'configurable-variant-update-images-event',
                            galleryImages
                        );
                    },

                    getSku(id, attribute) {
                        this.$axios
                            .get(
                                "{{ route('shop.api.products.get-sku', [':prodId', ':attrCode', ':attrName']) }}"
                                    .replace(':prodId', id)
                                    .replace(':attrCode', attribute.code)
                                    .replace(':attrName', attribute.selectedValue)
                            )
                            .then(response => {
                                console.log(response.data);
                                this.$emitter.emit(
                                    'configurable-variant-update-sku-event',
                                    response.data
                                );
                            })
                            .catch(error => {});
                    },
                },
            });
        </script>
    @endpush
@endif
