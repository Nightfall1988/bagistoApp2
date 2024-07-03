<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.wholesale.create.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center mt-3 max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('admin::app.wholesale.create.title')
        </p>
    </div>

    <!-- Page Header -->

    <div class="grid gap-2.5">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
                <form method="POST" action="{{ route('wholesale.wholesale.update', $wholesale->id) }}">
                    @csrf
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label for="name" class="block text-sm font-medium text-gray-700">Wholesale name</label>
                            <input type="text" name="name" id="name" value="{{ $wholesale->name }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="discount_percentage" class="block text-sm font-medium text-gray-700">Discount Percentage</label>
                            <input type="text" name="discount_percentage" id="discount_percentage" value="{{ $wholesale->discount_percentage }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="batch_amount" class="block text-sm font-medium text-gray-700">Batch Amount</label>
                            <input type="text" name="batch_amount" id="batch_amount" value="{{ $wholesale->batch_amount }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>


                        <div class="flex flex-row gap-4">
                            <div class="sm:col-span-6">
                                <label for="product_name" class="block text-sm font-medium text-gray-700">Product Name</label>
                                <input type="text" name="product_name" id="product_name" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <div id="found_products"></div>
                            </div>
                            <div>
                                <label for="individual" class="block text-sm font-medium text-gray-700">Individual wholesale option</label>
                                <input type="checkbox" name='individual'></input>
                            </div>
                        </div>
                    </div>

                        <div class="mt-5">
                            <button type="submit" class="primary-button">@lang('admin::app.wholesale.edit-btn')</button>
                        </div>
                </form>
        </div>
        </div>
    </div>

    <script>

        document.addEventListener('DOMContentLoaded', function () {
        const productInput = document.getElementById('product_name');
        const productSuggestions = document.getElementById('product_suggestions');

        productInput.addEventListener('keyup', function () {
            console.log(productInput)

            const query = this.value.trim();

            if (query !== '') {
                fetch(`{{ route("wholesale.wholesale.product.search") }}?query=${query}`)
                    .then(response => response.json())
                    .then(data => {
                        productSuggestions.innerHTML = '';
                        data.forEach(product => {
                            const listItem = document.createElement('li');
                            listItem.textContent = product.name;
                            productSuggestions.appendChild(listItem);
                        });
                        productSuggestions.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching product suggestions:', error);
                    });
            } else {
                productSuggestions.style.display = 'none';
            }
        });

        // productSuggestions.addEventListener('click', function (event) {
        //     if (event.target.tagName === 'LI') {
        //         productInput.value = event.target.textContent;
        //         productSuggestions.style.display = 'none';
        //     }
        // });
    });
    </script>
</x-admin::layouts>