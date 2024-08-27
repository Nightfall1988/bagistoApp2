<?php
namespace App\Services;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Contracts\Category;

class CategoryImportService {

    protected $categoryRepository;

    protected Category $category1; 
    protected Category $category2; 

    public function __construct(
        CategoryRepository $categoryRepository,
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    public function importMidoceanData($variant)
    {
        $categoryList = [];

        ini_set('memory_limit', '512M');

        $slug1 = $this->normalizeSlug($variant->category_level1);
        $category1 = $this->categoryRepository->findBySlug($slug1);

        if ($category1) {
            $this->category1 = $category1;
            $categoryList[] = $this->category1->id;
        } else {
            $data = [
                "locale" => "en",
                "name" => $variant->category_level1,
                "description" => $variant->category_level1,
                "slug" => $slug1,
                "meta_title" => "",
                "meta_keywords" => "",
                "meta_description" => "",
                "status" => "1",
                "position" => "1",
                "display_mode" => "products_and_description",
                "attributes" => [
                    0 => "11",
                    1 => "23",
                    2 => "24",
                    3 => "25"
                ]
            ];

            $this->category1 = $this->categoryRepository->create($data);
            $categoryList[] = $this->category1->id;
        }

        if (isset($variant->category_level2)) {
            $slug2 = $this->normalizeSlug($variant->category_level2);

            $category2 = $this->categoryRepository->findBySlug($slug2);

            if ($category2) {
                $this->category2 = $category2;
                $categoryList[] = $this->category2->id;
            } else {
                $data = [
                    "locale" => "en",
                    "name" => $variant->category_level2,
                    "parent_id" => $this->category1->id, 
                    "description" => $variant->category_level2,
                    "slug" => $slug2,
                    "meta_title" => "",
                    "meta_keywords" => "",
                    "meta_description" => "",
                    "status" => "1",
                    "position" => "2",
                    "display_mode" => "products_and_description",
                    "attributes" => [
                        0 => "11",
                        1 => "23",
                        2 => "24",
                        3 => "25"
                    ]
                ];
                
                $this->category2 = $this->categoryRepository->create($data);
                $categoryList[] = $this->category2->id;
            }
        }

        return $categoryList;
    }

    function normalizeSlug($categoryName)
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $categoryName));
        return $slug;
    }

    public function importStrickerCategories($optional, $midocean_to_stricker_category, $midocean_to_stricker_subcategory) {
        ini_set('memory_limit', '512M');

        $categoryList = [];
        $slug1 = $this->normalizeSlug($midocean_to_stricker_category[$optional['Type']]);
        $category1 = $this->categoryRepository->findBySlug($slug1);
        
        if (isset($optional['Type']) && $optional['Type'] != '') {
            if (array_key_exists($optional['Type'], $midocean_to_stricker_category)) {
                $slug1 = $this->normalizeSlug($midocean_to_stricker_category[$optional['Type']]);
                $category1 = $this->categoryRepository->findBySlug($slug1);
                if ($category1) {
                    $this->category1 = $category1;
                    $categoryList[] = $this->category1->id;
                }
            } else {
                $slug1 = $this->normalizeSlug($optional['Type']);
                $data = [
                    "locale" => "en",
                    "name" => $optional['Type'],
                    "description" => $optional['Type'],
                    "slug" => $slug1,
                    "meta_title" => "",
                    "meta_keywords" => "",
                    "meta_description" => "",
                    "status" => "1",
                    "position" => "1",
                    "display_mode" => "products_and_description",
                    "attributes" => [
                        0 => "11",
                        1 => "23",
                        2 => "24",
                        3 => "25"
                    ]
                ];
    
                $this->category1 = $this->categoryRepository->create($data);
                $categoryList[] = $this->category1->id;
            }
        }

        if (isset($optional['SubType']) && $optional['SubType'] != '') {
            if (array_key_exists($optional['SubType'], $midocean_to_stricker_subcategory)) {
                $slug2 = $this->normalizeSlug($midocean_to_stricker_subcategory[$optional['SubType']]);
                $category2 = $this->categoryRepository->findBySlug($slug2);
                if ($category2) {
                    $this->category2 = $category2;
                    $categoryList[] = $this->category2->id;
                }
            } else {
                $slug2 = $this->normalizeSlug($optional['SubType']);

                $data = [
                    "locale" => "en",
                    "name" => $optional['SubType'],
                    "description" => $optional['SubType'],
                    "slug" => $slug2,
                    "meta_title" => "",
                    "meta_keywords" => "",
                    "meta_description" => "",
                    "status" => "1",
                    "position" => "2",
                    "display_mode" => "products_and_description",
                    "attributes" => [
                        0 => "11",
                        1 => "23",
                        2 => "24",
                        3 => "25"
                    ]
                ];
    
                $this->category2 = $this->categoryRepository->create($data);
                $categoryList[] = $this->category2->id;
                }
            }
        
        return $categoryList;
    }

    public function importXDConnectsCategories($variant, $midocean_to_xdconnects_category, $midocean_to_xdconnects_subcategory) {
        $categoryList = [];
        if (isset($variant->MainCategory) && (string)$variant->MainCategory != '') {
            if (array_key_exists((string)$variant->MainCategory, $midocean_to_xdconnects_category)) {
                $slug1 = $this->normalizeSlug($midocean_to_xdconnects_category[(string)$variant->MainCategory]);
                $category1 = $this->categoryRepository->findBySlug($slug1);
                if ($category1) {
                    $this->category1 = $category1;
                    $categoryList[] = $this->category1->id;
                }
            } else {
                $slug1 = $this->normalizeSlug((string)$variant->MainCategory);
                $data = [
                    "locale" => "en",
                    "name" => (string)$variant->MainCategory,
                    "description" => (string)$variant->MainCategory,
                    "slug" => $slug1,
                    "meta_title" => "",
                    "meta_keywords" => "",
                    "meta_description" => "",
                    "status" => "1",
                    "position" => "1",
                    "display_mode" => "products_and_description",
                    "attributes" => [
                        0 => "11",
                        1 => "23",
                        2 => "24",
                        3 => "25"
                    ]
                ];
    
                $this->category1 = $this->categoryRepository->create($data);
                $categoryList[] = $this->category1->id;
            }
        }

        if (isset($variant->SubCategory) && (string)$variant->SubCategory != '') {
            if (array_key_exists((string)$variant->SubCategory, $midocean_to_xdconnects_subcategory)) {
                $slug2 = $this->normalizeSlug($midocean_to_xdconnects_subcategory[(string)$variant->SubCategory]);
                $category2 = $this->categoryRepository->findBySlug($slug2);
                if ($category2) {
                    $this->category2 = $category2;
                    $categoryList[] = $this->category2->id;
                }
            } else {
                $slug2 = $this->normalizeSlug((string)$variant->SubCategory);

                $data = [
                    "locale" => "en",
                    "name" => (string)$variant->SubCategory,
                    "description" => (string)$variant->SubCategory,
                    "slug" => $slug2,
                    "meta_title" => "",
                    "meta_keywords" => "",
                    "meta_description" => "",
                    "status" => "1",
                    "position" => "2",
                    "display_mode" => "products_and_description",
                    "attributes" => [
                        0 => "11",
                        1 => "23",
                        2 => "24",
                        3 => "25"
                    ]
                ];
    
                $this->category2 = $this->categoryRepository->create($data);
                $categoryList[] = $this->category2->id;
            }
        }
            return $categoryList;
    }
}