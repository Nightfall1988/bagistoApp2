<?php

namespace Hitexis\Attribute\Repositories;

use Webkul\Core\Eloquent\Repository;

class AttributeOptionTranslationRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Hitexis\Attribute\Models\AttributeOptionTranslation';
    }
}
