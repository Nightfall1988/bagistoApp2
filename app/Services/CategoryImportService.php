<?php
namespace App\Services;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Contracts\Category;

class CategoryImportService {

    protected $categoryRepository;

    protected Category $category1; 
    protected Category $category2; 
    protected Category $category3; 


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
                "locale" => "all",
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
                    "locale" => "all",
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


        if (isset($variant->category_level3)) {
            $slug3 = $this->normalizeSlug($variant->category_level3);

            $category3 = $this->categoryRepository->findBySlug($slug3);


            if ($category3) {
                $this->category3 = $category3;
                $categoryList[] = $this->category3->id;
            } else {
                $data = [
                    "locale" => "all",
                    "name" => $variant->category_level3,
                    "parent_id" => $this->category2->id, 
                    "description" => $variant->category_level3,
                    "slug" => $slug3,
                    "meta_title" => "",
                    "meta_keywords" => "",
                    "meta_description" => "",
                    "status" => "1",
                    "position" => "3",
                    "display_mode" => "products_and_description",
                    "attributes" => [
                        0 => "11",
                        1 => "23",
                        2 => "24",
                        3 => "25"
                    ]
                ];
    
                $this->category3 = $this->categoryRepository->create($data);
                $categoryList[] = $this->category3->id;
            }
        }

        return $categoryList;
    }

    function normalizeSlug($categoryName)
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $categoryName));
        return $slug;
    }
}