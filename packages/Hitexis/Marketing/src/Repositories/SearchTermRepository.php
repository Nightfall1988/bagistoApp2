<?php

namespace Hitexis\Marketing\Repositories;

use Webkul\Core\Eloquent\Repository;

class SearchTermRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Hitexis\Marketing\Contracts\SearchTerm';
    }

    public function findOneWhere(array $where, $columns = ['*'])
    {
        $model = $this->findWhere($where, $columns);

        if (isset($model->items) && sizeof($model->items == 0)) {
            
            $model = $this->findWhereAttribute($where, $columns);
        }
        return $model->first();
    }
}
