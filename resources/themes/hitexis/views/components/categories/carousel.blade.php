<v-categories-carousel
    src="{{ $src }}"
    title="{{ $title }}"
    navigation-link="{{ $navigationLink ?? '' }}"
    base-url="{{ url('/') }}" <!-- Passing base URL dynamically -->
>
    <x-shop::shimmer.categories.carousel
        :count="8"
        :navigation-link="$navigationLink ?? false"
    />
</v-categories-carousel>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-categories-carousel-template"
    >
        <div
            class="container mt-14 max-lg:px-8 max-sm:mt-5 flex justify-around"
            v-if="!isLoading && categories?.length"
        >
            <div class="relative flex justify-center" style="max-width: 1240px;">
                <div
                    ref="swiperContainer"
                    class="scrollbar-hide flex gap-10 overflow-auto scroll-smooth max-sm:gap-4"
                >
                    <div
                        class="grid min-w-[120px] max-w-[120px] grid-cols-1 justify-items-center gap-4 font-medium"
                        v-for="category in categories"
                        :key="category.id"
                    >
                        <a
                            :href="category.slug"
                            class="h-[110px] w-[110px] rounded-full bg-zinc-100"
                            :aria-label="category.name"
                        >
                            <!-- Display the category image using category.logo_path -->
                            <img
                                v-if="category.logo_path"
                                :src="modifiedUrl + category.logo_path"
                                alt="Category Image"
                                class="h-[110px] w-[110px] rounded-full"
                            />
                        </a>

                        <a
                            :href="category.slug"
                            class=""
                        >
                            <p
                                class="text-center text-lg text-black max-sm:font-normal"
                                v-text="category.name"
                            >
                            </p>
                        </a>
                    </div>
                </div>

                <span
                    class="icon-arrow-left-stylish absolute -left-10 top-9 flex h-[50px] w-[50px] cursor-pointer items-center justify-center rounded-full border border-black bg-white text-2xl transition hover:bg-black hover:text-white max-lg:-left-7"
                    role="button"
                    aria-label="@lang('shop::components.carousel.previous')"
                    tabindex="0"
                    @click="swipeLeft"
                >
                </span>

                <span
                    class="icon-arrow-right-stylish absolute -right-6 top-9 flex h-[50px] w-[50px] cursor-pointer items-center justify-center rounded-full border border-black bg-white text-2xl transition hover:bg-black hover:text-white max-lg:-right-7"
                    role="button"
                    aria-label="@lang('shop::components.carousel.next')"
                    tabindex="0"
                    @click="swipeRight"
                >
                </span>
            </div>
        </div>

        <!-- Category Carousel Shimmer -->
        <template v-if="isLoading">
            <x-shop::shimmer.categories.carousel
                :count="8"
                :navigation-link="$navigationLink ?? false"
            />
        </template>
    </script>

    <script type="module">
        app.component('v-categories-carousel', {
            template: '#v-categories-carousel-template',

            props: [
                'src',
                'title',
                'navigationLink',
                'baseUrl',
            ],

            data() {
                return {
                    isLoading: true,
                    categories: [],
                    offset: 323,
                };
            },

            mounted() {
                this.getCategories();
                this.editImgUrl();
            },

            methods: {
                getCategories() {
                    this.$axios.get(this.src)
                        .then(response => {
                            this.isLoading = false;
                            this.categories = response.data.data;
                        })
                        .catch(error => {
                            console.error(error);
                        });
                },

                swipeLeft() {
                    const container = this.$refs.swiperContainer;
                    container.scrollLeft -= this.offset;
                },

                swipeRight() {
                    const container = this.$refs.swiperContainer;
                    container.scrollLeft += this.offset;
                },

                editImgUrl() {
                    this.modifiedUrl = this.baseUrl + '/storage/';
                    console.log(this.modifiedUrl);
                }
            },
        });
    </script>
@endPushOnce
