<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.wholesale.index.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center mt-3 max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('admin::app.wholesale.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            @if (bouncer()->hasPermission('wholesale.create'))
                <a 
                    href="{{ route('wholesale.wholesale.create') }}"
                    class="primary-button"
                >
                    @lang('admin::app.wholesale.create-btn')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid src="{{ route('wholesale.wholesale.index') }}" />

</x-admin::layouts>