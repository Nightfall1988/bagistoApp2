<v-print-calculator :product="{{ json_encode($product) }}">

</v-print-calculator>
    @push('scripts')
        <script
            type="text/x-template"
            id="v-print-calculator-template"
        >
            <div class="w-[455px] max-w-full">
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
                                    </template>
                                </template>
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
        </script>

        <script type="module">
            app.component('v-print-calculator', {
            template: '#v-print-calculator-template',

            props: ['product'],

            data() {
                return {
                    selectedTechnique: '',
                    quantity: 1,
                    currentTechnique: null,
                    calculatedPrice: 0,
                };
            },

            watch: {
                selectedTechnique() {
                    this.updateCurrentTechnique();
                },
                quantity() {
                    this.updatePrice();
                },
            },

            methods: {
                updateCurrentTechnique() {
                    this.currentTechnique = this.product.print_techniques.find(
                        technique => technique.description === this.selectedTechnique
                    );
                    this.updatePrice();
                },

                updatePrice() {
                    if (!this.currentTechnique || !this.quantity) return;

                    const techniques = this.product.print_techniques.filter(
                        technique => technique.description === this.selectedTechnique
                    ).sort((a, b) => a.minimum_quantity - b.minimum_quantity);

                    const applicableTechnique = techniques.find(
                        technique => this.quantity >= technique.minimum_quantity
                    );

                    this.calculatedPrice = applicableTechnique ? applicableTechnique.price * this.quantity : 0;
                },
            },

            mounted() {
                console.log(this.product)
                if (this.product.print_techniques.length > 0) {
                    this.selectedTechnique = this.product.print_techniques[0].description;
                    this.updateCurrentTechnique();
                }
            },
        });

        </script>
    @endpush
