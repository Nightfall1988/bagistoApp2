<?php

namespace Hitexis\Wholesale\Http\Controllers;

use Webkul\Admin\Http\Controllers\Controller;
use Hitexis\Wholesale\Repositories\WholesaleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Hitexis\Wholesale\Http\Requests\WholesaleRequest;
use Hitexis\Product\Repositories\HitexisProductRepository as ProductRepository;
use Hitexis\Admin\DataGrids\Wholesale\WholesaleDataGrid;
use Hitexis\Product\Models\Product;

class WholesaleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        WholesaleRepository $wholesaleRepository,
        ProductRepository $productRepository
        )
    {
        $this->wholesaleRepository = $wholesaleRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(WholesaleDataGrid::class)->toJson();
        }

        return view('wholesale::wholesale.index');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('wholesale::wholesale.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $wholesaleDeal = $this->wholesaleRepository->create([
            'name' => request('name'),
            'batch_amount' => request('batch_amount'),
            'discount_percentage' => request('discount_percentage'),
            'status' => 'Active',
            'type' => 'Local',
        ]);

        
        return redirect()->route('wholesale.wholesale.index');
    }

    public function search(Request $request) {
        $this->wholesaleRepository->search();
    }

    public function edit($id)
    {
        $wholesale = $this->wholesaleRepository->findOrFail($id);
        return view('wholesale::wholesale.edit', compact('wholesale'));
    }

    
    public function update(Request $request, $id)
    {
        if (isset($request->product_name)) {
            $product = $this->productRepository->findByAttributeCode('name', $request->product_name);
        }

        $wholesale = $this->wholesaleRepository->update(request()->all(), $id);
        
        if (isset($product)) {
            if (!$product->wholesales->contains($wholesale->id)) {
                $product->wholesales()->attach($wholesale->id);
            }
        }

        session()->flash('success', trans('admin::app.wholesale.update-success'));

        return redirect()->route('wholesale.wholesale.index');
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            // Event::dispatch('marketing.campaigns.delete.before', $id);

            $this->wholesaleRepository->delete($id);

            // Event::dispatch('marketing.campaigns.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.wholesale.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->message,
            ]);
        }
    }


}
