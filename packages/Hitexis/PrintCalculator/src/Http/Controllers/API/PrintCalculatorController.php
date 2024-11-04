<?php

namespace Hitexis\PrintCalculator\Http\Controllers\API;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\PrintCalculator\Repositories\PrintTechniqueRepository;
use Hitexis\Product\Models\ProductProxy;
use Illuminate\Support\Facades\DB;

class PrintCalculatorController extends Controller
{
    use DispatchesJobs, ValidatesRequests;

    public function __construct(
        HitexisProductRepository $productRepository,
        PrintTechniqueRepository $printRepository
    ) {
        $this->productRepository = $productRepository;
        $this->printRepository = $printRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('printcalculator::shop.index');
    }

    public function getTechnique($id)
    {
        $technique = PrintTechnique::findOrFail($id);
        return response()->json($technique);
    }

    public function getProductPrintData($product_id)
    {
        $product = Product::with('print_techniques.print_manipulation')->find($product_id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    public function calculatePricing(Request $request)
    {
        // Retrieve input values
        $productId = $request->input('product_id');
        $techniqueId = $request->input('technique_id');
        $quantity = $request->input('quantity');
        $positionId = $request->input('position_id');
        $setup = $request->input('setup');
        $variantId = $request->input('variantId');

        $product = $this->productRepository->findOrFail($productId);

        // 'midocean'
        if ($product->supplier->supplier_code == '3CDCB852-2E30-43B6-A078-FA95A51DCA3C') {
            if ($product->type == 'configurable') {
                $product = $this->productRepository->with([
                    'productPrintData.printingPositions.printTechnique.variableCosts',
                    'productPrintData.printManipulation'
                ])->findOrFail($productId);

                $allPrintData = $product->productPrintData;

                $filteredPrintData = $allPrintData->filter(function ($printData) use ($positionId, $techniqueId) {
                    return $printData->printingPositions->contains(function ($position) use ($positionId, $techniqueId) {
                        return $position->id == $positionId && $position->printTechnique->contains('technique_id', $techniqueId);
                    });
                });

                $allPrintData = $filteredPrintData;

            } elseif ($product->type == 'simple') {
                $product = $this->productRepository->with([
                    'parent.productPrintData.printingPositions.printTechnique.variableCosts',
                    'parent.productPrintData.printManipulation'
                ])->findOrFail($productId);

                if ($product->parent) {
                    $allPrintData = $product->parent->productPrintData;

                    // Filter the printData for the specific positionId and techniqueId
                    $filteredPrintData = $allPrintData->filter(function ($printData) use ($positionId, $techniqueId) {
                        return $printData->printingPositions->contains(function ($position) use ($positionId, $techniqueId) {
                            return $position->id == $positionId && $position->printTechnique->contains('technique_id', $techniqueId);
                        });
                    });
                    $allPrintData = $filteredPrintData;

                }
            }
        }

        // xdconnects
        if ($product->supplier->supplier_code == 'ae59fecb-9cc2-4e32-b4c8-c05ff3e19a41') {
            if ($product->type == 'configurable') {

                $product = $this->productRepository->with([
                    'variants.productPrintData.printingPositions.printTechnique.variableCosts',
                    'variants.productPrintData'
                ])->findOrFail($product->id);

                $allPrintData = $product->variants[0]->productPrintData;
                foreach ($allPrintData as $printData) {
                    foreach ($printData->printingPositions as $position) {
                        foreach ($position->printTechnique as $technique) {
        
                            if ($position->id == $positionId && $technique->technique_id == $techniqueId) {
                                $selectedTechnique = $technique;
                                break;
                            }
                        }
                    }

                }
            } elseif ($product->type == 'simple') {
                $product = $this->productRepository->with([
                    'productPrintData.printingPositions.printTechnique.variableCosts',
                    'productPrintData'
                ])->findOrFail($product->id);

                $allPrintData = $product->productPrintData;

                foreach ($allPrintData as $printData) {

                    foreach ($printData->printingPositions as $position) {
                        // var_dump($position->id,$positionId, $position);
                        // echo "\n";
                        foreach ($position->printTechnique as $technique) {
        
                            if ($position->id == $positionId && $technique->technique_id == $techniqueId) {
                                $selectedTechnique = $technique;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // stricker
        if ($product->supplier->supplier_code == 'D99EA47D-397E-4C7B-BB0F-F5CBCAC4ED92') {
            if ($product->type == 'configurable') {
                $product = $this->productRepository->with([
                    'productPrintData.printingPositions.printTechnique.variableCosts',
                    'productPrintData.printManipulation'
                ])->findOrFail($productId);
    
                $allPrintData = $product->productPrintData;

                // Filter the printData for the specific positionId and techniqueId
                $filteredPrintData = $allPrintData->filter(function ($printData) use ($positionId, $techniqueId) {
                    return $printData->printingPositions->contains(function ($position) use ($positionId, $techniqueId) {
                        return $position->id == $positionId && $position->printTechnique->contains('technique_id', $techniqueId);
                    });
                });

            } elseif ($product->type == 'simple') {
                $product = $this->productRepository->with([
                    'parent.productPrintData.printingPositions.printTechnique.variableCosts',
                    'parent.productPrintData.printManipulation'
                ])->findOrFail($productId);
    
                if ($product->parent) {
                    $allPrintData = $product->parent->productPrintData;
                }
            }
        }

        // Find the correct printing position
        $printingPosition = [];

        foreach ($allPrintData as $printData) {

            foreach ($printData->printingPositions as $position) {
                // var_dump($position->id,$positionId, $position);
                // echo "\n";
                foreach ($position->printTechnique as $technique) {

                    if ($position->id == $positionId && $technique->technique_id == $techniqueId) {
                        $selectedTechnique = $technique;
                        break;
                    }
                }
            }
        }
    
        if (!isset($selectedTechnique)) {
            return response()->json(['message' => 'Print technique not found'], 404);
        }
    
        $setupCost = floatval(str_replace(',', '.', $selectedTechnique->setup));
        $repeatSetupCost = floatval(str_replace(',', '.', $selectedTechnique->setup_repeat));
    
        $variableCosts = $selectedTechnique->variableCosts;
        $pricingData = [];
        foreach ($variableCosts as $cost) {
            $pricingData = array_merge($pricingData, json_decode($cost->pricing_data, true));
        }
    
        $applicablePrice = null;
    
        // Find the applicable price for the quantity
        foreach ($pricingData as $i => $data) {
            $minQt = intval(str_replace('.', '', $data['MinQt']));
            $maxQt = isset($pricingData[$i + 1])
                ? intval(str_replace('.', '', $pricingData[$i + 1]['MinQt'])) - 1
                : PHP_INT_MAX;
    
            if ($quantity >= $minQt && $quantity <= $maxQt) {
                $applicablePrice = floatval($data['Price']);
                break;
            }
        }
    
        // Fallback to the first price if no range matched
        if (is_null($applicablePrice)) {
            $applicablePrice = floatval($pricingData[0]['Price']);
        }
    
        // Calculate print total
    
        // Calculate product price for the given quantity
        $productPriceQty = $product->price * $quantity;
    
        // Manipulation price calculation (retrieved from productPrintData)
        $manipulationPrice = 0;
        if (isset($product->productPrintData[0]->printManipulation)) {
            $manipulationPrice = floatval($product->productPrintData[0]->printManipulation->price) * $quantity;
        }

        $printTotal = ($applicablePrice * $quantity) + $manipulationPrice + $setupCost;
        $totalProductAndPrint = $productPriceQty + $printTotal;

        $printFee = $applicablePrice * intval($quantity);
        // Return the calculated data as JSON response
        return response()->json([
            'price' => $applicablePrice,
            'setup_cost' => $setupCost,
            'total_price' => core()->formatPrice($printTotal),
            'technique_print_fee' => $applicablePrice,
            'print_fee' => $printFee, // Assuming this needs custom calculation or backend logic
            'product_price_qty' => $productPriceQty,
            'total_product_and_print' => core()->formatPrice((round($totalProductAndPrint, 2))),
            'print_manipulation' => round($manipulationPrice, 2),
            'print_manipulation_single_price' => isset($product->productPrintData[0]->printManipulation)  ? round(floatval($product->productPrintData[0]->printManipulation->price), 2) : 0,            
            'print_full_price' => core()->formatPrice((round($printTotal, 2)))
        ]);
    }

    public function calculatePricingCart() {
        $techniqueName = request()->input('techniqueName');
        $items = request()->input('items');
        $pricingResults = [];

        // Loop through each cart item
        foreach ($items as $i => $item) {

            $product = $this->productRepository->findByAttributeCode('url_key', $item['product_url_key']);

            $productId = $product->id; // Assuming the product ID is available in the cart item
            $quantity = $item['quantity']; // Quantity from cart item

            // Fetch the technique by product_id and technique description (name)
            $technique = DB::table('print_techniques')
                ->where('product_id', $productId)
                ->where('description', $techniqueName)
                ->first();

            if ($technique) {
                // Decode the pricing data JSON
                $pricingData = json_decode($technique->pricing_data, true);

                // Set default price to null
                $applicablePrice = null;

                // Iterate over the pricing data to find the correct price based on the quantity range
                for ($i = 0; $i < count($pricingData); $i++) {
                    // Convert MinQt to a proper integer for comparison
                    $minQt = intval(str_replace('.', '', $pricingData[$i]['MinQt']));
                    $maxQt = isset($pricingData[$i + 1]) 
                        ? intval(str_replace('.', '', $pricingData[$i + 1]['MinQt'])) 
                        : PHP_INT_MAX; // Removed the -1 to avoid boundary issues

                    // Check if quantity falls within the range, including exact matches like 99
                    if ($quantity >= $minQt && $quantity < $maxQt) {
                        $applicablePrice = floatval($pricingData[$i]['Price']);
                        break;
                    }
                }

                // Fallback to the first tier if none matched (in case of single item or missing data)
                if (is_null($applicablePrice)) {
                    $applicablePrice = floatval($pricingData[0]['Price']);
                }

                // Calculate the total price for this item
                $totalPrice = $applicablePrice * $quantity;

                // Add this item's pricing result to the results array
                $pricingResults[] = [
                    'product_id' => $productId,
                    'technique' => $technique->description,
                    'quantity' => $quantity,
                    'unit_price' => $applicablePrice,
                    'total_price' => $totalPrice,
                ];
            }
        }

        // Return the pricing results
        return response()->json($pricingResults);
    }

}
