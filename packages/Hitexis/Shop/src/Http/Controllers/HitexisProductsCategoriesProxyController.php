<?php

namespace Hitexis\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Marketing\Repositories\URLRewriteRepository;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Product\Models\ProductProxy;
use Hitexis\Product\Repositories\ProductInventoryRepository;
use Webkul\Theme\Repositories\ThemeCustomizationRepository;
use Webkul\Shop\Http\Controllers\ProductsCategoriesProxyController as ProductsCategoriesBaseController;
use Illuminate\Database\Eloquent\Collection;

class HitexisProductsCategoriesProxyController extends Controller
{
    /**
     * Using const variable for status
     *
     * @var int Status
     */
    const STATUS = 1;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected HitexisProductRepository $productRepository,
        protected ThemeCustomizationRepository $themeCustomizationRepository,
        protected URLRewriteRepository $urlRewriteRepository,
        protected ProductInventoryRepository $inventoryRepository
    ) {
    }

    /**
     * Show product or category view. If neither category nor product matches, abort with code 404.
     *
     * @return \Illuminate\View\View|\Exception
     */
    public function index(Request $request)
    {
        $quantities = [];

        $slugOrURLKey = urldecode(trim($request->getPathInfo(), '/'));

        /**
         * Support url for chinese, japanese, arabic and english with numbers.
         */

        if (! preg_match('/^([\x{0621}-\x{064A}\x{4e00}-\x{9fa5}\x{3402}-\x{FA6D}\x{3041}-\x{30A0}\x{30A0}-\x{31FF}_a-z0-9-]+\/?)+$/u', $slugOrURLKey)) {
            visitor()->visit();

            $customizations = $this->themeCustomizationRepository->orderBy('sort_order')->findWhere([
                'status'     => self::STATUS,
                'channel_id' => core()->getCurrentChannel()->id,
            ]);

            return view('hitexis-shop::home.index', compact('customizations'));
        }

        $category = $this->categoryRepository->findBySlug($slugOrURLKey);

        if ($category) {
            visitor()->visit($category);

            return view('hitexis-shop::categories.view', [
                'category' => $category,
                'params'   => [
                    'sort'  => request()->query('sort'),
                    'limit' => request()->query('limit'),
                    'mode'  => request()->query('mode'),
                ],
            ]);
        }

        $product = $this->productRepository->findBySlug($slugOrURLKey);

        if ($product) {
            if (
                ! $product->url_key
                || ! $product->visible_individually
                || ! $product->status
            ) {
                abort(404);
            }

            $printData = null;

            switch ($product->supplier->supplier_code) {
                // midocean
                case '3CDCB852-2E30-43B6-A078-FA95A51DCA3C':
                    if ($product->type == 'configurable') {
                        $product = ProductProxy::with([
                            'productPrintData.printManipulation',                  // Get print manipulations through product print data
                            'productPrintData.printingPositions.printTechnique', // Get printing positions and their techniques
                            'productPrintData.printingPositions.printTechnique.variableCosts' // Get variable costs for print techniques
                        ])->find($product->id);

                        $printData = $product->productPrintData;

                    } else {
                        // If the product is simple, load the relationships of the parent (configurable) product
                        $product = ProductProxy::with([
                            'parent.productPrintData.printManipulation',
                            'parent.productPrintData.printingPositions.printTechnique',
                            'parent.productPrintData.printingPositions.printTechnique.variableCosts'
                        ])->find($product->id);
        
                        // Use the parent's print techniques if the product has a parent
                        if ($product->parent) {
                            $parent = $product->parent;
                            $printData = $parent->productPrintData;
                        }
                    }

                break;
                // xdconnects
                case 'ae59fecb-9cc2-4e32-b4c8-c05ff3e19a41':
                    if ($product->type == 'configurable') {
                        $product = $this->productRepository->with([
                            'variants.productPrintData.printingPositions.printTechnique.variableCosts',
                            'variants.productPrintData.printManipulation'
                        ])->findOrFail($product->id);
    

                        $printData = $product->variants[0]->productPrintData;
                    } elseif ($product->type == 'simple') {
                        $product = $this->productRepository->with([
                            'productPrintData.printingPositions.printTechnique.variableCosts',
                            'productPrintData.printManipulation'
                        ])->findOrFail($product->id);
    
                        $printData = $product->productPrintData;
                    }

                break;

                // stricker
                case 'D99EA47D-397E-4C7B-BB0F-F5CBCAC4ED92':
                    if ($product->type == 'configurable') {
                        $product = ProductProxy::with([
                            'productPrintData.printManipulation',                  // Get print manipulations through product print data
                            'productPrintData.printingPositions.printTechnique', // Get printing positions and their techniques
                            'productPrintData.printingPositions.printTechnique.variableCosts' // Get variable costs for print techniques
                        ])->find($product->id);

                        $printData = $product->productPrintData;

                        // Initialize an empty array to store the final results.
                        $filteredPrintData = [];

                        // dd($printData[0]->printingPositions );
                        foreach ($printData as $productPrintData) {
                            foreach ($productPrintData->printingPositions as $position) {
                                // Use an associative array to track the lowest price technique by description.
                                $lowestPriceByDescription = [];
                        
                                foreach ($position->printTechnique as $technique) {
                                    $description = $technique->description;
                                    $lowestPrice = null;
                                    $lowestPriceVariableCost = null;
                        
                                    // Iterate over each variable cost entry for this technique
                                    foreach ($technique->variableCosts as $variableCost) {
                                        $pricingData = json_decode($variableCost->pricing_data, true);
                        
                                        // Find the entry where 'MinQt' is 1
                                        $minPriceEntry = collect($pricingData)->firstWhere('MinQt', '1');
                        
                                        if ($minPriceEntry) {
                                            $price = (float) $minPriceEntry['Price'];
                        
                                            // Check if we should update the lowest price for this description
                                            if (
                                                !isset($lowestPriceByDescription[$description]) ||
                                                $price < $lowestPriceByDescription[$description]['price']
                                            ) {
                                                // Store the lowest price and the technique
                                                $lowestPriceByDescription[$description] = [
                                                    'price' => $price,
                                                    'technique' => $technique,
                                                ];
                                            }
                                        }
                                    }
                                }
                        
                                // Reassign the filtered techniques explicitly
                                $filteredTechniques = collect($lowestPriceByDescription)
                                    ->map(fn($entry) => $entry['technique'])
                                    ->values();
                        
                                // Ensure the filtered list is assigned back to the position
                                $position->setRelation('printTechnique', $filteredTechniques);
                            }
                        }                                             

                    } else {
                        // If the product is simple, load the relationships of the parent (configurable) product
                        $product = ProductProxy::with([
                            'parent.productPrintData.printManipulation',
                            'parent.productPrintData.printingPositions.printTechnique',
                            'parent.productPrintData.printingPositions.printTechnique.variableCosts'
                        ])->find($product->id);
        
                        // Use the parent's print techniques if the product has a parent
                        if ($product->parent) {
                            $parent = $product->parent;
                            $printData = $parent->productPrintData;
                        }
                    }
                    break;
            }

            visitor()->visit($product);

            if ($product->type == 'simple') {
                $quantities[$product->sku] = $product->totalQuantity();
            } elseif ($product->type == 'configurable') {
                foreach ($product->variants as $variant) {
                    $quantities[$variant->sku] = $variant->totalQuantity();
                }
            }

            if (empty($printData)) {
                return view('hitexis-shop::products.view', compact('product', 'quantities'));
            } else {
                return view('hitexis-shop::products.view', compact('product', 'printData', 'quantities'));
            }
        }

        /**
         * If category is not found, try to find it by slug.
         * If category is found by slug, redirect to category path.
         */
        $trimmedSlug = last(explode('/', $slugOrURLKey));

        $category = $this->categoryRepository->findBySlug($trimmedSlug);

        if ($category) {
            return redirect()->to($trimmedSlug, 301);
        }

        /**
         * If neither category nor product matches,
         * try to find it by url rewrite for category.
         */
        $categoryURLRewrite = $this->urlRewriteRepository->findOneWhere([
            'entity_type'  => 'category',
            'request_path' => $slugOrURLKey,
            'locale'       => app()->getLocale(),
        ]);

        if ($categoryURLRewrite) {
            return redirect()->to($categoryURLRewrite->target_path, $categoryURLRewrite->redirect_type);
        }

        /**
         * If neither category nor product matches,
         * try to find it by url rewrite for product.
         */
        $productURLRewrite = $this->urlRewriteRepository->findOneWhere([
            'entity_type'  => 'product',
            'request_path' => $slugOrURLKey,
        ]);

        if ($productURLRewrite) {
            return redirect()->to($productURLRewrite->target_path, $productURLRewrite->redirect_type);
        }

        abort(404);
    }
    
}
