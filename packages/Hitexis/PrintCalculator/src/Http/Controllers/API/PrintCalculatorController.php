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
        $setup = $request->input('setup-price');
    
        // Fetch the product along with its print data relationships
        $product = $this->productRepository->with([
            'productPrintData.printingPositions.printTechnique.variableCosts',
            'productPrintData.printManipulation' // Move printManipulation to productPrintData
        ])->findOrFail($productId);
    
        
        // Find the correct printing position
        $printingPosition = null;
        foreach ($product->productPrintData as $printData) {
            foreach ($printData->printingPositions as $position) {
                if ($position->id == $positionId) {
                    $printingPosition = $position;
                    break;
                }
            }
            if ($printingPosition) {
                break;
            }
        }
    
        // Ensure we found the correct printing position
        if (!$printingPosition) {
            return response()->json(['message' => 'Printing position not found'], 404);
        }
    
        // Find the selected print technique from the position's techniques
        $selectedTechnique = $printingPosition->printTechnique->firstWhere('technique_id', $techniqueId);
    
        if (!$selectedTechnique) {
            return response()->json(['message' => 'Print technique not found'], 404);
        }
    
        // Setup costs
        $setupCost = floatval(str_replace(',', '.', $selectedTechnique->setup));
        $repeatSetupCost = floatval(str_replace(',', '.', $selectedTechnique->setup_repeat));
    
        // Fetch the pricing data from the variable costs
        $variableCosts = $selectedTechnique->variableCosts;
        $pricingData = [];
        foreach ($variableCosts as $cost) {
            $pricingData = array_merge($pricingData, json_decode($cost->pricing_data, true));
        }
    
        // Default applicable price
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
        $printTotal = $applicablePrice * $quantity;
    
        // Calculate product price for the given quantity
        $productPriceQty = $product->price * $quantity;

        $totalProductAndPrint = $productPriceQty + $printTotal;
    
        // Manipulation price calculation (retrieved from productPrintData)
        $manipulationPrice = 0;
        if (isset($product->productPrintData[0]->printManipulation)) {
            $manipulationPrice = floatval($product->productPrintData[0]->printManipulation->price) * $quantity;
        }
    
        $printFee = $applicablePrice * intval($quantity);
        // Return the calculated data as JSON response

        return response()->json([
            'price' => $applicablePrice,
            'setup_cost' => $setupCost,
            'total_price' => core()->formatPrice($printTotal),
            'technique_print_fee' => $applicablePrice,
            'print_fee' => $printFee, // Assuming this needs custom calculation or backend logic
            'product_price_qty' => $productPriceQty,
            'total_product_and_print' => $totalProductAndPrint,
            'print_manipulation' => round($manipulationPrice, 2),
            'print_full_price' => core()->formatPrice((round($manipulationPrice, 2) + $printFee + $setupCost))
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
