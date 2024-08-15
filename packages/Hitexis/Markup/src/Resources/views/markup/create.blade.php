<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.markup.create.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center mt-3 max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('admin::app.markup.create.title')
        </p>
    </div>

    <div class="mt-5">
        <form method="POST" action="{{ route('markup.markup.store') }}">
            @csrf

            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label for="name" class="block text-sm font-medium text-gray-700">Markup name</label>
                    <input type="text" name="name" id="name" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div class="sm:col-span-3">
                    <label for="percentage" class="block text-sm font-medium text-gray-700">Percentage</label>
                    <input type="text" name="percentage" id="percentage" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div class="sm:col-span-3">
                    <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                    <input type="text" name="amount" id="amount" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div class="sm:col-span-6">
                    <label for="markup_unit" class="block text-sm font-medium text-gray-700">Markup Unit</label>
                    <input type="text" name="markup_unit" id="markup_unit" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div class="sm:col-span-6">
                    <label for="markup_type" class="block text-sm font-medium text-gray-700">Markup Type</label>
                    <input type="text" name="markup_type" id="markup_type" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
            </div>

            <div class="mt-5">
                <button type="submit" class="primary-button">@lang('admin::app.markup.create-btn')</button>
            </div>
        </form>
    </div>

    <script>

        document.addEventListener('DOMContentLoaded', function () {
        const productInput = document.getElementById('product_name');
        const productSuggestions = document.getElementById('product_suggestions');

        productInput.addEventListener('keyup', function () {
            console.log(productInput)

            const query = this.value.trim();

            if (query !== '') {
                fetch(`{{ route("markup.markup.product.search") }}?query=${query}`)
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