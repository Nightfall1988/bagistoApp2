<x-admin::layouts>

    <div id="app">
        <v-markup-manager></v-markup-manager>
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-markup-manager-template"
        >
            <div>
                <!-- Loading Overlay -->
                <div v-if="isLoading" class="modal-overlay">
                    <div class='flex flex-column'>
                        <div>
                            @lang('admin::app.markup.index.datagrid.adding-markup')
                        </div>
                        <div class="spinner">
                        </div>
                    </div>
                </div>

                <!-- Markup Options -->
                <div class="grid gap-1.5">
                    <p class="text-xl font-bold leading-6 text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.markup.create-title')
                    </p>
                </div>
                
                <!-- Form inside Modal -->
                <form @submit.prevent="submitForm" class="space-y-4 p-4">
                    <div class="flex flex-column gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">@lang('admin::app.markup.index.datagrid.name'):</label>
                            <input v-model="form.name" type="text" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div class="flex flex-row gap-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">@lang('admin::app.markup.index.datagrid.amount')</label>
                                <input v-model="form.amount" step="0.1" type="number" :disabled="percentageEnabled" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">@lang('admin::app.markup.index.datagrid.percentage')</label>
                                <input v-model="form.percentage" type="number" step="0.1" :disabled="amountEnabled" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div class='gap-5 mb-4'>
                        <div>
                            <div class='flex flex-row gap-5 mt-4 mb-4'>
                                <label for='form-percentage' class="block text-sm font-medium text-gray-700">@lang('admin::app.markup.index.datagrid.percentage')</label>
                                <input name='form-percentage' type="checkbox" v-model="percentageEnabled" @change="togglePercentage">
                                
                                <label for='form-amount' class="block text-sm font-medium text-gray-700">@lang('admin::app.markup.index.datagrid.amount')</label>
                                <input type="checkbox" name='form-amount' v-model="amountEnabled" @change="toggleAmount">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">@lang('admin::app.markup.index.datagrid.markup_type')</label>
                            <select v-model="form.markup_type" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="global">@lang('admin::app.markup.index.datagrid.global')</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white font-medium py-2 px-4 rounded-md shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            @lang('admin::app.components.datagrid.toolbar.mass-actions.submit')
                    </button>
                </form>
            </div>
        </script>

        <script type="module">
            app.component('v-markup-manager', {
                template: '#v-markup-manager-template',

                data() {
                    return {
                        isLoading: false, // Loading state for overlay
                        isGlobalMarkup: false,
                        isIndividualMarkup: false,
                        isModalVisible: false,
                        form: {
                            name: '',
                            amount: '',
                            percentage: '',
                            markup_type: 'individual',
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

                        // Adjust z-index based on modal visibility
                        this.toggleZIndex();
                    },

                    hideModal() {
                        this.isModalVisible = false;

                        // Adjust z-index based on modal visibility
                        this.toggleZIndex();
                    },

                    submitForm() {    
                        // Show loading screen
                        this.isLoading = true;

                        this.$axios.post("{{ route('admin.catalog.products.markup') }}", this.form)
                        .then(response => {
                                alert('Markup submitted successfully');
                                this.hideModal();
                                this.isLoading = false; // Hide loading screen
                            })
                            .catch(error => {
                                console.error('Error submitting markup', error);
                                this.isLoading = false; // Hide loading screen on error
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
                    },

                    toggleZIndex() {
                        let relaDiv = document.getElementsByClassName('relativediv');
                        relaDiv.forEach(element => {
                            if (this.isModalVisible) {
                                element.classList.remove('relative');
                                element.classList.add('z-auto');
                            } else {
                                element.classList.remove('z-auto');
                                element.classList.add('relative');
                            }
                        });
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
                z-index: 9999;
            }

            .spinner {
                border: 4px solid rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                border-top: 4px solid #fff;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
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

</x-admin::layouts>
