<v-markup-manager :product="{{ $product }}" ></v-markup-manager>

@php
    $isGlobalMarkup = false;
    $isIndividualMarkup = false;

    foreach ($product->markup as $markup) {
        if ($markup->markup_type == 'global') {
            $isGlobalMarkup = true;
        } else {
            $isIndividualMarkup = true;
        }
    }
@endphp

<script>
    window.isGlobalMarkup = @json($isGlobalMarkup);
    window.isIndividualMarkup = @json($isIndividualMarkup);
</script>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-markup-manager-template"
    >
        <div>
            <!-- Markup Options -->
            <div>
                <label>
                    <input type="checkbox" v-model="isGlobalMarkup" :disabled="hasGlobalMarkup">
                    Global Markup
                </label>

                <label>
                    <input type="checkbox" v-model="isIndividualMarkup" @change="openModal">
                    Individual Markup
                </label>
            </div>

            <!-- Show Global Markup Button -->
            <button v-if="hasGlobalMarkup" @click="showGlobalMarkup">
                See Markups
            </button>
            <!-- Modal -->
<div v-if="isModalVisible" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="hideModal">
    <div class="relative bg-white rounded-lg shadow-lg p-6 w-7/12 max-w-l">
        <!-- Close Button -->
        <div class='flex flex-between'>
            <div>
                <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-600" @click="hideModal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div>
            <!-- Modal Header -->
                <h2 class="text-xl font-semibold text-gray-800 mb-4 px-4 pt-4">Individual Markup Details</h2>
            </div>
        </div>
        <!-- Form inside Modal -->
<!-- Form inside Modal -->
        <form @submit.prevent="submitForm" class="space-y-4 p-4">
            <div class="flex flex-column gap-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name:</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div class="flex flex-row gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount:</label>
                        <input v-model="form.amount" step="0.1" type="number" :disabled="percentageEnabled" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Percentage:</label>
                        <input v-model="form.percentage" type="number" step="0.1" :disabled="amountEnabled" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
            </div>

            <div class='gap-5 mb-4'>
                <div>
                    <div class='flex flex-row gap-5 mt-4 mb-4'>
                        <label for='form-percentage' class="block text-sm font-medium text-gray-700">Percentage</label>
                        <input name='form-percentage' type="checkbox" v-model="percentageEnabled" @change="togglePercentage">
                        
                        <label for='form-amount' class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="checkbox" name='form-amount' v-model="amountEnabled" @change="toggleAmount">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Markup Type:</label>
                    <select v-model="form.markup_type" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="global">Global</option>
                        <option value="individual">Individual</option>
                    </select>
                </div>
            </div>

            <!-- Hidden Inputs -->
            <div class="hidden">
                <input type="hidden" name="sku" v-model="form.sku" value="{{ $product->sku }}" />
                <input type="hidden" v-model="form.description" name="description" value="{{ $product->description }}" />
                <input type="hidden" v-model="form.short_description" name="short_description" value="{{ $product->short_description }}" />
                <input type="hidden" v-model="form.url_key" name="url_key" value="{{ $product->url_key }}" />
                <input type="hidden" v-model="form.weight" name="weight" value="{{ $product->weight }}" />
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    class="w-full bg-blue-600 text-white font-medium py-2 px-4 rounded-md shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Submit
            </button>
        </form>
    </div>
</div>

        </div>
    </script>

    <script type="module">
        app.component('v-markup-manager', {
            template: '#v-markup-manager-template',
    
            props: {
                product: null,
            },
    
            data() {
                return {
                    isGlobalMarkup: window.isGlobalMarkup,
                    isIndividualMarkup: window.isIndividualMarkup,
                    isModalVisible: false,
                    form: {
                        name: '',
                        amount: '',
                        percentage: '',
                        markup_type: 'individual', 
                        sku: '',
                        description: '',
                        short_description: '',
                        url_key: '',
                        weight: '',
                    },
                    percentageEnabled: false,
                    amountEnabled: false,
                }
            },
    
            methods: {
                showGlobalMarkup() {
                    alert('Displaying global markups');
                },
    
                openModal() {
                    if (this.isIndividualMarkup) {
                        this.isModalVisible = true;
                    }
    
                    if (this.isModalVisible == true) {
                        let relaDiv = document.getElementsByClassName('relativediv')
                        relaDiv.forEach(element => {
                            if (element.classList.contains('relative')) {
                                element.classList.remove('relative');
                                element.classList.add('z-auto');
                                console.log(element);
                            }
                        });
                    } else {
                        let relaDiv = document.getElementsByClassName('relativediv')
                        relaDiv.forEach(element => {
                            if (!element.classList.contains('relative')) {
                                element.classList.remove('z-auto');
                                element.classList.add('relative');
                                console.log(element);
                            }
                        });
                    }
                },
    
                hideModal() {
                    this.isModalVisible = false;
                },
    
                submitForm() {    
                    this.$axios.post("{{ route('admin.catalog.products.markup', $product->id) }}", this.form)
                        .then(response => {
                            alert('Markup submitted successfully');
                            this.hideModal();
                        })
                        .catch(error => {
                            console.error('Error submitting markup', error);
                    });
                },
    
                togglePercentage() {
                    if (this.percentageEnabled) {
                        this.form.amount = ''; // Clear amount
                        this.amountEnabled = false; // Disable amount
                    }
                },
    
                toggleAmount() {
                    if (this.amountEnabled) {
                        this.form.percentage = ''; // Clear percentage
                        this.percentageEnabled = false; // Disable percentage
                    }
                }
            },
    
            watch: {
                'form.amount': function (newVal) {
                    if (newVal) {
                        this.amountEnabled = true;
                        this.percentageEnabled = false;
                    }
                },
                'form.percentage': function (newVal) {
                    if (newVal) {
                        this.percentageEnabled = true;
                        this.amountEnabled = false;
                    }
                }
            },
    
            computed: {
                isAmount() {
                    return this.amountEnabled && !!this.form.amount;
                },
    
                isPercentage() {
                    return this.percentageEnabled && !!this.form.percentage;
                }
            }
        });
    </script>
    
@endpushOnce

@pushOnce('styles')
    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 4px;
            position: relative;
            width: 80%;
            max-width: 600px;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }
    </style>
@endpushOnce
