<v-print-calculator :product="{{ json_encode($product) }}"></v-print-calculator>

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
                        <option v-for="technique in uniqueDescriptions" :key="technique" :value="technique">
                            @{{ technique }}
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
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.individual-product-price')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.quantity')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.print-fee')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.total-price')</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <tr v-for="technique in techniquesData" :key="technique.description" class="hover:bg-gray-100 transition-colors duration-150">
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.product_name }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.print_technique }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ parseFloat(product.price).toFixed(2) }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.quantity }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ parseFloat(technique.technique_print_fee).toFixed(2) }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">    @{{ ((Number(product.price || 0) + Number(technique.price || 0)) * Number(technique.quantity || 0)).toFixed(2) }}</td>
                        </tr>
                        <!-- Hidden inputs to hold technique-related data -->
                        <input name='technique-single-price' type='hidden' v-model="techniqueSinglePrice" />
                        <input name='technique-info' type='hidden' v-model="techniqueInfo" />
                        <input name='technique-price' type='hidden' v-model="techniquePrice" />
                    </tbody>
                </table>
            </div>
        </div>
    </script>
</div>

<script type="module">
    app.component('v-print-calculator', {
        template: '#v-print-calculator-template',

        props: ['product'],

        data() {
            return {
                selectedTechnique: '',
                currentTechnique: null,
                techniquesData: [],
                techniquePrice: '',
                techniqueInfo: '',
                techniqueSinglePrice: '',
            };
        },

        computed: {
            uniqueDescriptions() {
                const descriptionsSet = new Set();
                this.product.print_techniques.forEach(technique => {

                    if (technique.pricing_data != '[]') {
                        descriptionsSet.add(technique.description);
                    }
                });
                return Array.from(descriptionsSet);
            }
        },

        watch: {
            selectedTechnique() {
                this.updateCurrentTechnique();
            },

            selectedTechnique(newVal, oldVal) {
                if (newVal !== oldVal) {
                    this.updateCurrentTechnique();
                }
            },
        },

        methods: {
            updateCurrentTechnique() {
                this.currentTechnique = this.product.print_techniques.find(
                    technique => technique.description === this.selectedTechnique
                );
                this.calculatePrices();
            },

            calculatePrices() {
                const quantity = this.getQuantityFromFieldQty();
                if (!quantity || !this.currentTechnique) return;

                // Send data to the backend using Axios
                axios.get("{{ route('printcontroller.api.print.gettechnique') }}", {
                    params: {
                        technique_id: this.currentTechnique.id,
                        quantity: quantity,
                        product_id: this.product.id
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
                        total_product_and_print: data.total_product_and_print
                    }];

                    // Set techniqueSinglePrice to the calculated print fee
                    this.techniqueSinglePrice = parseFloat(data.technique_print_fee).toFixed(2);
                    this.techniqueInfo = this.currentTechnique.description;
                    this.techniquePrice = data.technique_print_fee.toFixed(2);
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
            this.observeQuantityChange();

            if (this.product.print_techniques.length > 0) {
                this.selectedTechnique = this.product.print_techniques[0].description;
                this.updateCurrentTechnique();
            }
        },
    });
</script>
@endpush
