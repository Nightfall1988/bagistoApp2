<?php

namespace Hitexis\Admin\DataGrids\Markup;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MarkupDataGrid extends DataGrid
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

        $queryBuilder = DB::table('markup')
            ->select(
                'markup.id',
                'markup.name',
                'markup.amount',
                'markup.percentage',
                'markup.markup_unit',
                'markup.currency',
                'markup.markup_type',
            );

        $this->addFilter('id', 'markup.id');

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
            'label'      => trans('admin::app.markup.index.datagrid.name'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'amount',
            'label'      => trans('admin::app.markup.index.datagrid.amount'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'percentage',
            'label'      => trans('admin::app.markup.index.datagrid.percentage'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        
        $this->addColumn([
            'index'      => 'markup_unit',
            'label'      => trans('admin::app.markup.index.datagrid.markup_unit'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        
        $this->addColumn([
            'index'      => 'currency',
            'label'      => trans('admin::app.markup.index.datagrid.currency'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'markup_type',
            'label'      => trans('admin::app.markup.index.datagrid.markup_type'),
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
        if (bouncer()->hasPermission('admin.markup.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('shop::app.wholesale.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('markup.markup.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('admin.markup.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('shop::app.markup.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('markup.markup.delete', $row->id);
                },
            ]);
        }
    }
}