<v-media {{ $attributes }} >
    <x-hitexis-shop::media.images.lazy
        class="mb-4 h-[284px] w-[284px] rounded-xl"
    />
</v-media>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-media-template"
    >
        <div class="mb-4 flex cursor-pointer flex-col rounded-lg">
            <div :class="{'border border-dashed border-gray-300 rounded-2xl': isDragOver }">
                <div
                    class="flex h-[284px] w-[284px] cursor-pointer flex-col items-center justify-center rounded-xl bg-zinc-100 hover:bg-gray-100"
                    v-if="uploadedFiles.isPicked"
                >
                    <div 
                        class="group relative flex h-[284px] w-[284px] justify-center"
                        @mouseenter="uploadedFiles.showDeleteButton = true"
                        @mouseleave="uploadedFiles.showDeleteButton = false"
                    >
                        <img
                            class="rounded-xl object-cover"
                            :src="uploadedFiles.url"
                            :class="{'opacity-25' : uploadedFiles.showDeleteButton}"
                            alt="Uploaded Image"
                        >

                        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 transform opacity-0 transition-opacity group-hover:opacity-100">
                            <span 
                                class="icon-bin cursor-pointer text-2xl text-black"
                                @click="removeFile"
                            >
                            </span>
                        </div>
                    </div>
                </div>

                <label 
                    for="file-input"
                    class="flex h-[284px] w-[284px] cursor-pointer flex-col items-center justify-center rounded-xl bg-zinc-100 hover:bg-gray-100"
                    v-show="! uploadedFiles.isPicked"
                    @dragover="onDragOver"
                    @dragleave="onDragLeave"
                    @drop="onDrop"
                >
                    <label 
                        for="file-input"
                        class="primary-button m-0 mx-auto block w-max rounded-2xl px-11 py-3 text-center text-base"
                    >
                        @lang('shop::app.components.media.add-attachments')
                    </label>

                    <input
                        type="hidden"
                        :name="name"
                        v-if="! uploadedFiles.isPicked"
                    />

                    <v-field
                        type="file"
                        class="hidden"
                        id="file-input"
                        :name="name"
                        :accept="acceptedTypes"
                        :rules="appliedRules"
                        :multiple="isMultiple"
                        @change="onFileChange"
                    >
                    </v-field>
                </label>
            </div>

            <div 
                class="flex items-center"
                v-if="isMultiple"
            >
                <ul class="justify-left mt-2 flex flex-wrap gap-2.5">
                    <li 
                        v-for="(file, index) in uploadedFiles"
                        :key="index"
                    >
                        <template v-if="isImage(file)">
                            <div 
                                class="group relative flex h-12 w-12 justify-center"
                                @mouseenter="file.showDeleteButton = true"
                                @mouseleave="file.showDeleteButton = false"
                            >
                                <img
                                    :src="file.url"
                                    :alt="file.name"
                                    class="max-h-12 min-w-12 rounded-xl"
                                    :class="{'opacity-25' : file.showDeleteButton}"
                                >

                                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 transform opacity-0 transition-opacity group-hover:opacity-100">
                                    <span 
                                        class="icon-bin cursor-pointer text-2xl text-black"
                                        @click="removeFile(index)"
                                    >
                                    </span>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div
                                class="group relative flex h-12 w-12 justify-center"
                                @mouseenter="file.showDeleteButton = true"
                                @mouseleave="file.showDeleteButton = false"
                            >
                                <video
                                    :src="file.url"
                                    :alt="file.name"
                                    class="max-h-[50px] min-w-[50px] rounded-xl"
                                    :class="{'opacity-25' : file.showDeleteButton}"
                                >
                                </video>

                                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 transform opacity-0 transition-opacity group-hover:opacity-100">
                                    <span 
                                        class="icon-bin cursor-pointer text-2xl text-black"
                                        @click="removeFile(index)"
                                    >
                                    </span>
                                </div>
                            </div>
                        </template>
                    </li>
                </ul>
            </div>
        </div>
    </script>

    <script type="module">
        app.component("v-media", {
            template: '#v-media-template',

            props: {
                name: {
                    type: String, 
                    default: 'attachments',
                }, 

                isMultiple: {
                    type: Boolean,
                    default: false,
                }, 

                rules: {
                    type: String,
                },

                acceptedTypes: {
                    type: String, 
                    default: 'image/*, video/*,'
                }, 

                label: {
                    type: String, 
                    default: 'Add attachments'
                }, 

                src: {
                    type: String,
                    default: ''
                }
            },

            data() {
                return {
                    uploadedFiles: [],

                    isDragOver: false,

                    appliedRules: '',
                };
            },

            created() {
                this.appliedRules = this.rules;

                if (this.src != '') {
                    this.appliedRules = '';

                    this.uploadedFiles = {
                        isPicked: true,
                        url: this.src,
                    }                        
                }
            },

            methods: {
                onFileChange(event) {
                    let files = event.target.files;

                    for (let i = 0; i < files.length; i++) {
                        let file = files[i];

                        let reader = new FileReader();

                        reader.onload = () => {
                            if (! this.isMultiple) {
                                this.uploadedFiles = {
                                    isPicked: true,
                                    name: file.name,
                                    url: reader.result,
                                }

                                return;
                            }

                            this.uploadedFiles.push({
                                name: file.name,
                                url: reader.result,
                                file: new File([file], file.name),
                            });
                        };

                        reader.readAsDataURL(file);
                    }
                },

                handleDroppedFiles(files) {
                    for (let i = 0; i < files.length; i++) {
                        let file = files[i];

                        let reader = new FileReader();
                        
                        reader.onload = () => {
                            if (! this.isMultiple) {
                                this.uploadedFiles = {
                                    isPicked: true,
                                    name: file.name,
                                    url: reader.result,
                                }

                                return;
                            }

                            this.uploadedFiles.push({
                                name: file.name,
                                url: reader.result,
                            });
                        };

                        reader.readAsDataURL(file);
                    }
                },

                isImage(file) {
                    if (! file.name) {
                        return;
                    }

                    return file.name.match(/\.(jpg|jpeg|png|gif)$/i);
                },

                onDragOver(event) {
                    event.preventDefault();

                    this.isDragOver = true;
                },

                onDragLeave(event) {
                    event.preventDefault();

                    this.isDragOver = false;
                },
                
                onDrop(event) {
                    event.preventDefault();

                    this.isDragOver = false;

                    let files = event.dataTransfer.files;

                    this.handleDroppedFiles(files);
                },

                removeFile(index) {
                    if (! this.isMultiple) {
                        this.uploadedFiles = [];

                        this.appliedRules = this.rules;
                        
                        return;
                    }

                    this.uploadedFiles.splice(index, 1);
                },
            },        
        });
    </script>
@endpushOnce
