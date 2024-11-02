<v-print-calculator ref="printCalculator" :product="{{ json_encode($product) }}" :printData="{{ json_encode($printData) }}"></v-print-calculator>

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

            <div class="price-grid bg-white rounded-lg shadow-lg overflow-hidden">
                <table class="w-full bg-gray-50 border-separate border-spacing-0">
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
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ technique.description }} </td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ Number(technique.setup_cost).toFixed(2) }} *</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ parseFloat(product.price).toFixed(2) }} *</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ parseFloat(manipulationPrice).toFixed(2) }} *</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ technique.quantity }}</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center">@{{ parseFloat(technique.technique_print_fee).toFixed(2) }} *</td>
                            <td class="px-6 py-4 border-b border-gray-200 text-center w-32">@{{ totalRowPrice }} * </td>
                        </tr>
                        <!-- Hidden inputs to hold technique-related data -->
                        <input name='technique-single-price' type='hidden' v-model="techniqueSinglePrice" />
                        <input name='technique-info' type='hidden' v-model="techniqueInfo" />
                        <input name='technique-price' type='hidden' v-model="techniquePrice" />
                        <input name='position-id' type='hidden' v-model="positionId" />
                        <input name='setup-price' type='hidden' v-model="setupPrice" />
                        <input name='print-manipulation' type='hidden' v-model="manipulationPrice" />
                        <input name='technique-id' type='hidden' v-model="techniqueId" /> <!-- New hidden input for technique ID -->
                        <input name='print-manipulation-single-price' type='hidden' v-model="manipulationSinglePrice" /> <!-- New hidden input for technique ID -->
                        <input name='variant-color' type='hidden' v-model="variantColor" />
                        <input name='variant-id' type='hidden' v-model="selectedOptionVariant" />
                    </tbody>
                    <p class="mt-4 ml-2 mb-2 text-sm text-zinc-500 max-sm:mt-4 max-xs:text-xs">
                        <i>* @lang('shop::app.products.view.price-no-tax')</i>
                    </p>
                </table>
            </div>
        </div>
    </script>
</div>

<script type="module">
    app.component('v-print-calculator', {
        template: '#v-print-calculator-template',

        props: ['product', 'printData'],

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
                variantColor: '', // New property for variant color
                allTechniques: []
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
                const print_fee = parseFloat(this.print_fee) || 0;

                return ((productPrice * quantity) + print_fee).toFixed(2);
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

                // Traverse through productPrintData, printingPositions, and printTechnique to get all techniques
                if (Array.isArray(this.printData)) {
                    this.printData.forEach(printData => {
                        if (Array.isArray(printData.printing_positions)) {
                            printData.printing_positions.forEach(position => {
                                
                                if (Array.isArray(position.print_technique)) {                         
                                    position.print_technique.forEach(technique => {   
                                        technique.position_id = position.id                                        
                                        technique.description = position.position_id + ' - ' + technique.description;
                                        this.allTechniques.push(technique);
                                    });
                                }
                            });
                        }
                    });
                }
            },

            updateCurrentTechnique() {
                if (this.selectedTechnique === 'no-technique') {
                    // Reset all technique-related properties when no technique is selected
                    this.techniquesData = [{
                        product_name: this.product.name,
                        techniqueInfo: "@lang('shop::app.products.view.calculator.no-technique')",
                        quantity: 0,
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
                    this.positionId = ''; // Reset positionId
                    this.setupPrice = 0;
                    this.manipulationPrice = 0;
                    this.manipulationSinglePrice = 0;
                    this.techniqueId = ''; // Reset technique ID
                    this.printFullPrice = ''; // Reset print fee
                } else {
                    // Split the `selectedTechnique` to get `technique_id` and `position_id`
                    const [technique_id, position_id] = this.selectedTechnique.split(':');
                    this.currentTechnique = this.allTechniques.find(
                        technique => technique.technique_id == technique_id && technique.position_id == position_id
                    );

                    if (this.currentTechnique) {
                        // Assign the technique-related values, including positionId
                        this.techniqueId = this.currentTechnique.technique_id;
                        this.techniqueInfo = this.currentTechnique.description;
                        this.setupPrice = this.currentTechnique.setup;
                        this.positionId = this.currentTechnique.position_id; // Update the positionId
                        this.calculatePrices();
                    } else {
                        console.warn('No technique found for the selectedTechnique:', this.selectedTechnique);
                    }
                }
            },

            calculatePrices() {
                
                const quantity = this.getQuantityFromFieldQty();
                if (!quantity || !this.currentTechnique) return;

                // Call to backend to calculate the price, passing the required params
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
                    
                    // Update techniquesData with backend-calculated data
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
                    
                    // Set techniqueSinglePrice to the calculated print fee
                    this.techniqueSinglePrice = parseFloat(data.technique_print_fee).toFixed(2);
                    this.techniquePrice = this.totalTechniquePrice;
                    this.positionId = this.positionId;
                    this.manipulationPrice = data.print_manipulation;
                    this.manipulationSinglePrice =  data.print_manipulation_single_price;
                    this.print_fee = data.print_fee;
                    this.printFullPrice =  data.print_full_price;
                    
                })
                .catch(error => {
                    console.error('Error calculating price:', error);
                });
            },

            getQuantityFromFieldQty() {
                const qtyField = document.querySelector('#field-qty input[type="hidden"]');
                return qtyField ? parseInt(qtyField.value, 10) : null;
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
        },

        mounted() {
            EventBus.$on('variant-id-updated', (variantId) => {
                this.selectedOptionVariant = variantId;
            });
            this.initializeTechniques();
            this.observeQuantityChange();

            // Check if `allTechniques` has any items and set the first one as selected
            if (this.allTechniques.length > 0) {
                const firstTechnique = this.allTechniques[0];
                this.selectedTechnique = `${firstTechnique.technique_id}:${firstTechnique.position_id}`; // Set the combined value
                this.updateCurrentTechnique();
            }
        },
    });
</script>
@endpush
