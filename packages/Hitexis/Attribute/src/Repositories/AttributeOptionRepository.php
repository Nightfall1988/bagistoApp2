<?php

namespace Hitexis\Attribute\Repositories;

use Illuminate\Http\UploadedFile;
use Webkul\Core\Eloquent\Repository;

class AttributeOptionRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Hitexis\Attribute\Models\AttributeOption';
    }

    /**
     * @return \Hitexis\Attribute\Contracts\AttributeOption
     */
    public function create(array $data)
    {
        $option = parent::create($data);

        $this->uploadSwatchImage($data, $option->id);

        return $option;
    }

    /**
     * @param  int  $id
     * @param  string  $attribute
     * @return \Hitexis\Attribute\Contracts\AttributeOption
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $option = parent::update($data, $id);

        $this->uploadSwatchImage($data, $id);

        return $option;
    }

    /**
     * @param  array  $data
     * @param  int  $optionId
     * @return void
     */
    public function uploadSwatchImage($data, $optionId)
    {
        if (empty($data['swatch_value'])) {
            return;
        }

        if ($data['swatch_value'] instanceof UploadedFile) {
            parent::update([
                'swatch_value' => $data['swatch_value']->store('attribute_option'),
            ], $optionId);
        }
    }

    function matchesPattern($name) {
        $pattern = '/^\d+ - [a-zA-Z]+$/';
        return preg_match($pattern, $name) === 1;
    }

    public function getOption($name)
    {
        if ($this->matchesPattern($name)) {
            $name = explode(' - ', $name)[1];
        }

        $result = $this->model->where('admin_name', $name)->first();
        if ($result === null) {
            return null;
        } else {
            return $result;
        }
    }

        /**
     * Create product.
     *
     * @return \Hitexis\Product\Contracts\Product
     */
    public function upserts(array $data)
    {                
        $existingOption = $this->findOneByField('admin_name', $data['admin_name']);

        if ($existingOption) {
            $option = $this->findOneByField('admin_name', $existingOption->admin_name);
            $option = $this->update($data,$existingOption->id);

        } else {
            $option = $this->create($data);
        }
    
        return $option;
    }
}
