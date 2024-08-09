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

    <x-admin::datagrid src="{{ route('markup.markup.index') }}" />

</x-admin::layouts>