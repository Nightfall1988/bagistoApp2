<?php

namespace Hitexis\PrintCalculator\Http\Controllers\API;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\PrintCalculator\Repositories\PrintTechniqueRepository;

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

        // dd($request->quantity);

        $product = $this->productRepository->find($request->product_id);

        $techniques = $this->printRepository
            ->where('description', '=', $request->type)
            ->orderBy('minimum_quantity', 'asc')
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
}
