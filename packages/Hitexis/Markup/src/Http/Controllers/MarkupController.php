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
        
        $markup = $this->markupRepository->create( $data );
        if($id) {
            $product = $this->productRepository->where('id',$id);
            $this->markupRepository->addMarkupToPrice( $product, $markup);
        }
        return redirect()->route('markup.markup.index');
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $markup = $this->markupRepository->findOrFail($id);

        return view('markup::markup.edit', compact('markup'));
    }

    public function search(Request $request) {
        $this->wholesaleRepository->search();
    }

    public function destroy(int $id): JsonResponse
    {
        try {

            $markup = $this->markupRepository->where('id', $id)->first();
            $markupId = $markup->id;
            $products = Product::whereHas('markup', function ($query) use ($markupId) {
                $query->where('markup_id', $markupId);
            })->get();

            if ($markup) {
                foreach ($products as $product) {
                    $this->markupRepository->subtractMarkupFromPrice($product,$markup);
                }
                $this->markupRepository->delete($id);
            }


            return new JsonResponse([
                'message' => trans('admin::app.markup.delete-success'),
            ]);
        } catch (\Exception $e) {
            dd($e);
            return new JsonResponse([
                'message' => $e->message,
            ]);
        }
    }
}
