<v-print-calculator :product="{{ json_encode($product) }}"></v-print-calculator>

@push('scripts')
<div class="mt-8">
    <script type="text/x-template" id="v-print-calculator-template">
        <div class="p-4 bg-gray-100 rounded-lg shadow-md">
            <div class="mb-4">
                <label for="technique" class="block text-sm font-medium text-gray-700 mb-2">
                    <h2 class="text-2xl font-bold text-indigo-600">Select Print Type</h2>
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
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.quantity')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.price')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.print-fee')</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">@lang('shop::app.products.view.calculator.total-price')</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <tr v-for="technique in techniquesData" :key="technique.description" class="hover:bg-gray-100 transition-colors duration-150">
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.product_name }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.print_technique }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.quantity }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.price }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.technique_print_fee }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ parseFloat(technique.total_price).toFixed(2) }}</td>
                        </tr>
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
            };
        },

        computed: {
            uniqueDescriptions() {
                const descriptionsSet = new Set();
                this.product.print_techniques.forEach(technique => {
                    descriptionsSet.add(technique.description);
                });
                return Array.from(descriptionsSet);
            }
        },

        watch: {
            selectedTechnique() {
                this.updateCurrentTechnique();
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

                // Parse the pricing data from JSON
                let pricingData = [];
                try {
                    pricingData = JSON.parse(this.currentTechnique.pricing_data);
                } catch (error) {
                    console.error('Error parsing pricing data:', error);
                    return;
                }

                // Find the applicable price for the given quantity
                const applicablePrice = pricingData
                    .filter(priceData => quantity >= priceData.MinQt)
                    .sort((a, b) => b.MinQt - a.MinQt)[0];

                if (applicablePrice) {
                    this.techniquesData = [{
                        product_name: this.product.name,
                        print_technique: this.currentTechnique.description,
                        quantity: quantity,
                        setup_cost: 0, // Placeholder, adjust if needed
                        total_price: applicablePrice.Price * quantity,
                        technique_print_fee: applicablePrice.Price,
                        price: applicablePrice.Price,
                        print_fee: 0 // Placeholder, adjust if needed
                    }];
                } else {
                    this.techniquesData = [];
                }
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
            }
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
