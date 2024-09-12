<?php

namespace Hitexis\PrintCalculator\Http\Controllers\API;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\PrintCalculator\Repositories\PrintTechniqueRepository;
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

    public function calculate(Request $request)
    {
        $this->validate(request(), [
            'product_id' => 'required|integer',
            'quantity' => 'required|integer',
            'type' => 'required|string',
        ]);

        $product = $this->productRepository->find($request->product_id);

        $techniques = $this->printRepository
            ->where('description', '=', $request->type)
            ->orderBy('description', 'asc')
            ->get();
            

        $selectedTechnique = null;

        // Loop through the techniques to find the correct one
        foreach ($techniques as $technique) {
            if ($request->quantity >= $technique->minimum_quantity) {
                $selectedTechnique = $technique;
            } else {
                break;
            }
        }

        $techniquePrintFee = $selectedTechnique->price;
        $price = $product->price;
        $quantity = $request->quantity;

        $setupCost = floatval(str_replace(',', '.', $selectedTechnique->setup));
        $totalPrice = ($price * $quantity) + $techniquePrintFee;

        return response()->json([
            'product_name' => $product->name,
            'print_technique' => $selectedTechnique->description,
            'quantity' => $quantity,
            'price' => number_format($price, 2),
            'technique_print_fee' => number_format($techniquePrintFee, 2),
            'total_price' => number_format($totalPrice, 2),
        ]);
    }

    private function calculateVariableCost($scales, $quantity)
    {
        foreach ($scales as $scale) {
            if ($quantity >= intval($scale['minimum_quantity'])) {
                $price = floatval(str_replace(',', '.', $scale['price']));
            }
        }

        return $price ?? 0; // Default to 0 if no price found
    }

    public function getTechnique($id)
    {
        $technique = PrintTechnique::findOrFail($id);
        return response()->json($technique);
    }

    public function getProductPrintData($product_id)
    {
        $product = Product::with('print_techniques.print_manipulations')->find($product_id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    public function calculatePrice(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $quantity = $request->input('quantity');
        $technique = $this->printRepository->with('manipulations')
                     ->findOrFail($request->input('print_technique_id'));
    
        $totalPrice = $product->base_price * $quantity;
        $totalPrice += $technique->setup;  // Adding the setup cost
    
        foreach ($technique->manipulations as $manipulation) {
            if (in_array($manipulation->id, $request->input('manipulations', []))) {
                $totalPrice += $manipulation->price * $quantity;
            }
        }
    
        return back()->with('calculatedPrice', $totalPrice);
    }
    
    public function calculatePricing(Request $request)
    {
        $techniqueId = $request->input('technique_id');
        $quantity = $request->input('quantity');
        $productId = $request->input('product_id');
        // Fetch the product and print technique
        $product = $this->productRepository->findOrFail($productId);
        $technique = $this->printRepository->where('id', $techniqueId)->first();

        // Perform calculation
        $setupCost = floatval(str_replace(',', '.', $technique->setup));
        $repeatSetupCost = floatval(str_replace(',', '.', $technique->setup_repeat));
        $pricingData = json_decode($technique->pricing_data, true);

        // Set default price to null
        $applicablePrice = null;

        // Iterate over the pricing data to find the correct price based on the quantity range
        for ($i = 0; $i < count($pricingData); $i++) {
            // Convert MinQt to a proper integer for comparison
            $minQt = intval(str_replace('.', '', $pricingData[$i]['MinQt']));
            $maxQt = isset($pricingData[$i + 1]) 
                ? intval(str_replace('.', '', $pricingData[$i + 1]['MinQt'])) - 1 
                : PHP_INT_MAX;

            if ($quantity >= $minQt && $quantity <= $maxQt) {
                $applicablePrice = floatval($pricingData[$i]['Price']);
                break;
            }
        }
        
        if (is_null($applicablePrice)) {
            $applicablePrice = floatval($pricingData[0]['Price']);
        }
        
        // Calculate print total
        $printTotal = $applicablePrice * $quantity;

        // Calculate total product price
        $productPriceQty = $product->price * $quantity;
        $totalProductAndPrint = $productPriceQty + $printTotal;

        // Return the calculated result
        return response()->json([
            'price' => $applicablePrice,
            'setup_cost' => $setupCost,
            'total_price' => $printTotal,
            'technique_print_fee' => $applicablePrice,
            'print_fee' => 0, // Adjust as needed
            'product_price_qty' => $productPriceQty,
            'total_product_and_print' => $totalProductAndPrint,
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
