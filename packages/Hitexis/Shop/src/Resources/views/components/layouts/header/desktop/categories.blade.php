<div class="m-auto">
    <div class="flex items-center gap-x-10 max-[1180px]:gap-x-5">
        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.before') !!}

        <v-desktop-categories>
            <div class="flex items-center gap-5">
                <span class="shimmer h-6 w-20 rounded" role="presentation"></span>
                <span class="shimmer h-6 w-20 rounded" role="presentation"></span>
                <span class="shimmer h-6 w-20 rounded" role="presentation"></span>
            </div>
        </v-desktop-categories>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.after') !!}
    </div>
</div>

@pushOnce('scripts')
    <script type="text/x-template" id="v-desktop-categories-template">

        <div class="flex items-center">
            <div v-for="category in categories" :key="category.id" class="group relative flex h-[77px] items-center border-b-[4px] border-transparent hover:border-b-[4px] hover:border-navyBlue">
                <a :href="category.url" class="inline-block px-5 uppercase text-mineShaft">@{{ category.name }}</a>
                <div v-if="category.children.length" class="pointer-events-none absolute top-[78px] z-[1] max-h-[580px] w-max max-w-[1260px] translate-y-1 overflow-auto overflow-x-auto border border-b-0 border-l-0 border-r-0 border-t border-[#F3F3F3] bg-white p-9 opacity-0 shadow-[0_6px_6px_1px_rgba(0,0,0,.3)] transition duration-300 ease-out group-hover:pointer-events-auto group-hover:translate-y-0 group-hover:opacity-100 group-hover:duration-200 group-hover:ease-in ltr:-left-9 rtl:-right-9">
                    <div class="aligns flex gap-x-[70px]">
                        <div v-for="pair in pairCategoryChildren(category)" class="grid w-full min-w-max max-w-[150px] flex-auto grid-cols-[1fr] content-start gap-5">
                            <template v-for="secondLevel in pair">
                                <p class="font-medium text-navyBlue">
                                    <a :href="secondLevel.url">@{{ secondLevel.name }}</a>
                                </p>
                                <ul v-if="secondLevel.children.length" class="grid grid-cols-[1fr] gap-3">
                                    <li v-for="thirdLevel in secondLevel.children" class="text-sm font-medium text-zinc-500">
                                        <a :href="thirdLevel.url">@{{ thirdLevel.name }}</a>
                                    </li>
                                </ul>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-desktop-categories', {
            template: '#v-desktop-categories-template',

            data() {
                return {
                    isLoading: true,
                    categories: [],
                };
            },

            mounted() {
                this.getCategories();
            },

            methods: {
                getCategories() {
                    this.$axios.get("{{ route('shop.api.categories.tree') }}")
                        .then(response => {
                            this.isLoading = false;
                            console.log(response.data.data);

                            this.categories = response.data.data;
                        })
                        .catch(error => {
                            console.error('Error fetching categories:', error);
                        });
                },

                pairCategoryChildren(category) {
                    return category.children.reduce((result, value, index, array) => {
                        if (index % 2 === 0) {
                            result.push(array.slice(index, index + 2));
                        }
                        return result;
                    }, []);
                }
            },
        });
    </script>
@endPushOnce
