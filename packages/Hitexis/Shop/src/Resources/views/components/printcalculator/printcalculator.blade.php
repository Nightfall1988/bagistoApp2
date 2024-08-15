<v-print-calculator :product="{{ json_encode($product) }}"></v-print-calculator>

@push('scripts')
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
                            <th class="px-6 py-3 border-b-2 border-indigo-700">Product Name</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">Technique</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">Quantity</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">Price</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">Technique Print Fee</th>
                            <th class="px-6 py-3 border-b-2 border-indigo-700">Total Price</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <tr v-for="technique in techniquesData" :key="technique.description" class="hover:bg-gray-100 transition-colors duration-150">
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.product_name }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.print_technique }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.quantity }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.price }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.technique_print_fee }}</td>
                            <td class="px-6 py-4 border-b border-gray-200">@{{ technique.total_price }}</td>
                        </tr>
                    </tbody>
                </table>
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
                    if (!quantity) return;

                    this.$axios.get('{{ route('printcontroller.api.print.calculate') }}', {
                        params: {
                            'product_id': this.product.id,
                            'quantity': quantity,
                            'type': this.selectedTechnique
                        }
                    })
                    .then(response => {
                        if (response && response.data) {
                            this.techniquesData = [{
                                product_name: response.data.product_name,
                                print_technique: response.data.print_technique,
                                quantity: response.data.quantity,
                                setup_cost: response.data.setup_cost,
                                total_price: response.data.total_price,
                                technique_print_fee: response.data.technique_print_fee,
                                price: response.data.price,
                                print_fee: response.data.print_fee
                            }];
                        }
                    })
                    .catch(error => {
                        if (error.response) {
                            if ([400, 422].includes(error.response.status)) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.data ? error.response.data.data.message : 'An error occurred' });
                                return;
                            }
                            this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                        } else if (error.request) {
                            console.error('No response received:', error.request);
                            this.$emitter.emit('add-flash', { type: 'error', message: 'No response from the server. Please try again later.' });
                        } else {
                            console.error('Error', error.message);
                            this.$emitter.emit('add-flash', { type: 'error', message: 'An error occurred while making the request.' });
                        }
                    });
                },

                getQuantityFromFieldQty() {
                    const qtyField = document.querySelector('#field-qty input[type="hidden"]');
                    return qtyField ? qtyField.value : null;
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
