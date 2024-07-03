<?php

namespace Hitexis\Admin\DataGrids\Wholesale;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WholesaleDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $whereInLocales = core()->getRequestedLocaleCode() === 'all'
            ? core()->getAllLocales()->pluck('code')->toArray()
            : [core()->getRequestedLocaleCode()];

        $queryBuilder = DB::table('wholesale')
            ->select(
                'wholesale.id',
                'wholesale.name',
                'wholesale.batch_amount',
                'wholesale.discount_percentage',
                'wholesale.status',
                'wholesale.type',
                'wholesale.product_id'
            );

        $this->addFilter('id', 'wholesale.id');

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.wholesale.index.datagrid.name'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'batch_amount',
            'label'      => trans('admin::app.wholesale.index.datagrid.batch_amount'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'discount_percentage',
            'label'      => trans('admin::app.wholesale.index.datagrid.discount_percentage'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        
    }

    // /**
    //  * Prepare actions.
    //  *
    //  * @return void
    //  */
    // public function prepareActions()
    // {
    //     $this->addAction([
    //         'icon'   => 'icon-view',
    //         'title'  => trans('admin::app.cms.index.datagrid.view'),
    //         'method' => 'GET',
    //         'index'  => 'url_key',
    //         'target' => '_blank',
    //         'url'    => function ($row) {
    //             return route('shop.cms.page', $row->url_key);
    //         },
    //     ]);

    //     if (bouncer()->hasPermission('cms.edit')) {
    //         $this->addAction([
    //             'icon'   => 'icon-edit',
    //             'title'  => trans('admin::app.cms.index.datagrid.edit'),
    //             'method' => 'GET',
    //             'url'    => function ($row) {
    //                 return route('admin.cms.edit', $row->id);
    //             },
    //         ]);
    //     }

    //     if (bouncer()->hasPermission('cms.delete')) {
    //         $this->addAction([
    //             'icon'   => 'icon-delete',
    //             'title'  => trans('admin::app.cms.index.datagrid.delete'),
    //             'method' => 'DELETE',
    //             'url'    => function ($row) {
    //                 return route('admin.cms.delete', $row->id);
    //             },
    //         ]);
    //     }
    // }

    // /**
    //  * Prepare mass actions.
    //  *
    //  * @return void
    //  */
    // public function prepareMassActions()
    // {
    //     if (bouncer()->hasPermission('cms.delete')) {
    //         $this->addMassAction([
    //             'title'  => trans('admin::app.cms.index.datagrid.delete'),
    //             'method' => 'POST',
    //             'url'    => route('admin.cms.mass_delete'),
    //         ]);
    //     }
    // }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('admin.wholesale.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('shop::app.wholesale.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('wholesale.wholesale.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('admin.wholesale.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('shop::app.wholesale.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('wholesale.wholesale.delete', $row->id);
                },
            ]);
        }
    }
}