<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.markup.index.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center mt-3 max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('admin::app.markup.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            @if (bouncer()->hasPermission('markup.create'))
                <a 
                    href="{{ route('markup.markup.create') }}"
                    class="primary-button"
                >
                    @lang('admin::app.markup.create-btn')
                </a>
            @endif
        </div>
    </div>

    <!-- Add loading overlay (hidden by default) -->
    <div id="loading-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0, 0, 0, 0.5); z-index:9999;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); color:white;">
            Loading...
        </div>
    </div>

    <x-hitexis-admin::datagrid src="{{ route('markup.markup.index') }}" @actionSuccess="handleActionSuccess" />

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function showLoadingScreen() {
                document.getElementById('loading-overlay').style.display = 'block';
            }

            function hideLoadingScreen() {
                document.getElementById('loading-overlay').style.display = 'none';
            }

            window.handleActionSuccess = function(responseData) {
                if (responseData && responseData.message && responseData.message.includes("deleted")) {
                    hideLoadingScreen();
                }
            }

            // Extend datagrid action to show loading screen specifically for delete actions
            const datagridTable = document.querySelector('v-datagrid-table');
            if (datagridTable) {
                datagridTable.addEventListener('click', function(event) {
                    if (event.target.closest('.icon-delete')) {
                        showLoadingScreen();
                    }
                });
            }
        });
    </script>
</x-admin::layouts>
