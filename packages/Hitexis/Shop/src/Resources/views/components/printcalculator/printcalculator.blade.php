<v-print-calculator
    ref="printCalculator"
    :product="{{ json_encode($product) }}"
    @if(isset($printData))
        :printData="{{ json_encode($printData) }}"
    @endif
></v-print-calculator>

@push('scripts')
<div class="mt-8">
    <script type="text/x-template" id="v-print-calculator-template">
        <div class="p-4 bg-gray-100 rounded-lg shadow-md">
            <div class="mb-4">
                <label for="technique" class="block text-sm font-medium text-gray-700 mb-2">
                    <h2 class="text-2xl font-bold text-indigo-600">@lang('shop::app.products.view.calculator.title')</h2>
                </label>
                <div>
                    <select v-model="selectedTechnique" @change="updateCurrentTechnique" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="no-technique">@lang('shop::app.products.view.calculator.no-technique')</option>
                        <option v-for="technique in allTechniques" :key="technique.technique_id" :value="`${technique.technique_id}:${technique.position_id}`">
                            @{{ technique.description }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Responsive Table Layout -->
            <div class="price-grid bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Standard Table for Larger Screens -->
                <table v-if="!isSmallScreen" class="w-full bg-gray-50 border-separate border-spacing-0">
                    <thead class="bg-indigo-100 text-midnightBlue uppercase text-sm">
                        <tr>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.product-name')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.technique')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.setup-cost')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.individual-product-price')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.manipulation')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.quantity')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.print-fee')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.total-price')</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <tr v-for="technique in techniquesData" :key="technique.description" class="hover:bg-gray-100 transition-colors duration-150">
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ product.name }}</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ techniqueInfo }}</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ Number(technique.setup_cost).toFixed(2) }} *</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ parseFloat(product.price).toFixed(2) }} *</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ parseFloat(manipulationPrice).toFixed(2) }} *</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ technique.quantity }}</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ parseFloat(technique.technique_print_fee).toFixed(2) }} *</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center w-32">@{{ totalRowPrice }} *</td>
                        </tr>
                    </tbody>
                    <p class="mt-4 ml-2 mb-2 text-sm text-zinc-500 max-sm:mt-4 max-xs:text-xs">
                        <i>* @lang('shop::app.products.view.price-no-tax')</i>
                    </p>
                </table>

                <!-- Responsive Stacked Layout for Smaller Screens -->
                <div v-else class="price-grid bg-white rounded-lg shadow-lg overflow-hidden">
                    <div v-for="technique in techniquesData" :key="technique.description" class="border-b border-gray-200 p-4">
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold">@lang('shop::app.products.view.calculator.product-name')</span>
                            <span>@{{ product.name }}</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold">@lang('shop::app.products.view.calculator.technique')</span>
                            <span>@{{ techniqueInfo }}</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold">@lang('shop::app.products.view.calculator.setup-cost')</span>
                            <span>@{{ Number(technique.setup_cost).toFixed(2) }} *</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold">@lang('shop::app.products.view.calculator.individual-product-price')</span>
                            <span>@{{ parseFloat(product.price).toFixed(2) }} *</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold">@lang('shop::app.products.view.calculator.manipulation')</span>
                            <span>@{{ parseFloat(manipulationPrice).toFixed(2) }} *</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold">@lang('shop::app.products.view.calculator.quantity')</span>
                            <span>@{{ technique.quantity }}</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold">@lang('shop::app.products.view.calculator.print-fee')</span>
                            <span>@{{ parseFloat(technique.technique_print_fee).toFixed(2) }} *</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold">@lang('shop::app.products.view.calculator.total-price')</span>
                            <span>@{{ totalRowPrice }} *</span>
                        </div>
                    </div>
                    <p class="mt-4 ml-2 mb-2 text-sm text-zinc-500 max-sm:mt-4 max-xs:text-xs">
                        <i>* @lang('shop::app.products.view.price-no-tax')</i>
                    </p>
                </div>
            </div>
        </div>
    </script>
</div>

<script type="module">
    app.component('v-print-calculator', {
        template: '#v-print-calculator-template',

        props: {
            product: {
                type: Object,
                required: true
            },
            printData: {
                type: Array,
                default: () => []
            }
        },
        data() {
            return {
                selectedTechnique: '',
                currentTechnique: null,
                techniquesData: [],
                techniquePrice: '',
                techniqueInfo: '',
                techniqueSinglePrice: '',
                positionId: '',
                setupPrice: '',
                manipulationPrice: 0,
                manipulationSinglePrice: 0,
                selectedOptionVariant: '',
                techniqueId: '',
                variantColor: '',
                allTechniques: [],
                totalRowPrice: '',
                isSmallScreen: window.innerWidth < 760 // Determine if screen is small initially
            };
        },

        computed: {
            totalTechniquePrice() {
                if (this.techniquesData.length > 0) {
                    const technique = this.techniquesData[0];
                    return ((Number(technique.price) * technique.quantity) + Number(technique.setup_cost) + Number(technique.printManipulation)).toFixed(2);
                }
                return "0.00";
            },
            
            totalRowPrice() {
                const productPrice = parseFloat(this.product.price) || 0;
                const quantity = this.techniquesData.length > 0 ? this.techniquesData[0].quantity : 0;
                const print_fee = parseFloat(this.technique_print_fee) || 0;

                this.totalRowPrice = (((Number(this.print_fee) + productPrice) * quantity) + Number(this.setupPrice) + Number(this.manipulationPrice)).toFixed(2)
            },
        },

        watch: {
            selectedTechnique() {
                this.updateCurrentTechnique();
            },
        },

        methods: {
            updateVariantColor(color) {
                this.variantColor = color;
            },
            initializeTechniques() {
                if (this.printData.length > 0) {
                    this.printData.forEach(printData => {
                        if (Array.isArray(printData.printing_positions)) {
                            printData.printing_positions.forEach(position => {
                                if (Array.isArray(position.print_technique)) {
                                    position.print_technique.forEach(technique => {
                                        technique.position_id = position.id;
                                        technique.description = `${position.position_id} - ${technique.description}`;
                                        this.allTechniques.push(technique);
                                    });
                                }
                            });
                        }
                    });
                }
            },

            updateCurrentTechnique() {
                const quantity = this.getQuantityFromFieldQty() || 1;
                
                if (this.selectedTechnique === 'no-technique') {
                    this.techniquesData = [{
                        product_name: this.product.name,
                        techniqueInfo: "@lang('shop::app.products.view.calculator.no-technique')",
                        quantity: 1,
                        price: 0,
                        setup_cost: 0,
                        total_price: 0,
                        technique_print_fee: 0,
                        print_fee: 0,
                        product_price_qty: 0,
                        total_product_and_print: 0,
                        printManipulation: 0,
                    }];
                    this.techniqueSinglePrice = 0;
                    this.techniqueInfo = "@lang('shop::app.products.view.calculator.no-technique')";
                    this.techniquePrice = 0;
                    this.positionId = '';
                    this.setupPrice = 0;
                    this.manipulationPrice = 0;
                    this.manipulationSinglePrice = 0;
                    this.techniqueId = '';
                    this.printFullPrice = '';
                    this.totalRowPrice = (parseFloat(this.product.price).toFixed(2) * this.getQuantityFromFieldQty()).toFixed(2)
                } else {
                    const [technique_id, position_id] = this.selectedTechnique.split(':');
                    this.currentTechnique = this.allTechniques.find(
                        technique => technique.technique_id == technique_id && technique.position_id == position_id
                    );

                    if (this.currentTechnique) {
                        this.techniqueId = this.currentTechnique.technique_id;
                        this.techniqueInfo = this.currentTechnique.description;
                        this.setupPrice = this.currentTechnique.setup;
                        this.positionId = this.currentTechnique.position_id;
                        this.calculatePrices();
                    } else {
                        console.warn('No technique found for the selectedTechnique:', this.selectedTechnique);
                    }
                }
            },

            calculatePrices() {
                const quantity = this.getQuantityFromFieldQty();
                if (!quantity || !this.currentTechnique) return;

                axios.get("{{ route('printcontroller.api.print.gettechnique') }}", {
                    params: {
                        technique_id: this.techniqueId,
                        quantity: quantity,
                        product_id: this.product.id,
                        position_id: this.positionId,
                        setup: this.setupPrice,
                        variantId: this.selectedOptionVariant
                    }
                })
                .then(response => {
                    const data = response.data;
                    this.techniquesData = [{
                        product_name: this.product.name,
                        print_technique: this.currentTechnique.description,
                        quantity: quantity,
                        price: data.price,
                        setup_cost: data.setup_cost,
                        total_price: data.total_price,
                        technique_print_fee: data.technique_print_fee,
                        print_fee: data.print_fee,
                        product_price_qty: data.product_price_qty,
                        total_product_and_print: data.total_product_and_print,
                        printManipulation: data.print_manipulation,
                        manipulationSinglePrice: data.print_manipulation_single_price
                    }];
                    this.techniqueSinglePrice = parseFloat(data.technique_print_fee).toFixed(2);
                    this.techniquePrice = this.totalTechniquePrice;
                    this.positionId = this.positionId;
                    this.manipulationPrice = data.print_manipulation;
                    this.manipulationSinglePrice =  data.print_manipulation_single_price;
                    this.print_fee = data.print_fee;
                    this.printFullPrice =  data.print_full_price;
                    this.totalRowPrice = data.total_product_and_print;
                })
                .catch(error => {
                    console.error('Error calculating price:', error);
                });
            },

            getQuantityFromFieldQty() {
                const qtyField = document.querySelector('#field-qty input[type="hidden"]');
                return qtyField ? parseInt(qtyField.value, 10) : 1;
            },

            observeQuantityChange() {
                const qtyField = document.querySelector('#field-qty input[type="hidden"]');
                if (!qtyField) return;

                const observer = new MutationObserver(() => {
                    this.updateCurrentTechnique();
                });

                observer.observe(qtyField, {
                    attributes: true,
                    attributeFilter: ['value']
                });
            },

            checkScreenSize() {
                this.isSmallScreen = window.innerWidth < 900;
            }
        },

        mounted() {
            EventBus.$on('variant-id-updated', (variantId) => {
                this.selectedOptionVariant = variantId;
            });
            this.initializeTechniques();
            this.observeQuantityChange();

            window.addEventListener('resize', this.checkScreenSize);

            if (this.allTechniques.length > 0) {
                const firstTechnique = this.allTechniques[0];
                this.selectedTechnique = `${firstTechnique.technique_id}:${firstTechnique.position_id}`;
                this.updateCurrentTechnique();
            }
        },

        beforeUnmount() {
            window.removeEventListener('resize', this.checkScreenSize);
        },
    });
</script>
@endpush
