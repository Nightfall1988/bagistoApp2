<?php

namespace Hitexis\Markup\Http\Controllers;

use Webkul\Admin\Http\Controllers\Controller;
use Hitexis\Markup\Repositories\MarkupRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Hitexis\Markup\Http\Requests\MarkupRequest;
use Hitexis\Product\Repositories\HitexisProductRepository as ProductRepository;
use Hitexis\Admin\DataGrids\Markup\MarkupDataGrid;
use Hitexis\Product\Models\Product;

class MarkupController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        MarkupRepository $markupRepository,
        ProductRepository $productRepository
        )
    {
        $this->markupRepository = $markupRepository;
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
            return app(MarkupDataGrid::class)->toJson();
        }

        return view('markup::markup.index');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('markup::markup.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id = null)
    {
        $data = [
            'name' => request('name'),
            'amount' => request('amount'),
            'percentage' => request('percentage'),
            'currency' => request('currency'),            
            'markup_unit' => request('markup_unit'),
            'markup_type' => request('markup_type'),
        ];

        if (request('product_name')) {
            $data['product_name'] = request('product_name');
        }
        
        $deal = $this->markupRepository->create( $data );

        return redirect()->route('markup.markup.index');
    }

    public function search(Request $request) {
        $this->wholesaleRepository->search();
    }

    // public function edit($id)
    // {
    //     $wholesale = $this->wholesaleRepository->findOrFail($id);
    //     return view('wholesale::wholesale.edit', compact('wholesale'));
    // }

    
    // public function update(Request $request, $id)
    // {
    //     if (isset($request->product_name)) {
    //         $product = $this->productRepository->findByAttributeCode('name', $request->product_name);
    //     }

    //     $wholesale = $this->wholesaleRepository->update(request()->all(), $id);
        
    //     if (isset($product)) {
    //         if (!$product->wholesales->contains($wholesale->id)) {
    //             $product->wholesales()->attach($wholesale->id);
    //         }
    //     }

    //     session()->flash('success', trans('admin::app.wholesale.update-success'));

    //     return redirect()->route('wholesale.wholesale.index');
    // }

    public function destroy(int $id): JsonResponse
    {
        try {
            // Event::dispatch('marketing.campaigns.delete.before', $id);

            $this->markupRepository->delete($id);

            // Event::dispatch('marketing.campaigns.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.markup.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->message,
            ]);
        }
    }


}
