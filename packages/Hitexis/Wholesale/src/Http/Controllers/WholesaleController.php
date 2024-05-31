<?php

namespace Hitexis\Wholesale\Http\Controllers;

use Webkul\Admin\Http\Controllers\Controller;
use Hitexis\Wholesale\Repositories\WholesaleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Hitexis\Wholesale\Http\Requests\WholesaleRequest;
use Hitexis\Admin\DataGrids\Wholesale\WholesaleDataGrid;
use Webkul\Admin\Serivices\StrickerProductService;
use Hitexis\Product\Models\Product;

class WholesaleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected WholesaleRepository $wholesaleRepository)
    {
        dd('fasdafasf');
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

        return view('admin::wholesale.index');
    }

    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::wholesale.create');
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

        
        return redirect()->route('admin.wholesale.index');
    }

    public function search(Request $request) {
        $this->wholesaleRepository->search();
    }

}
