<?php

namespace Hitexis\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Hitexis\Attribute\Contracts\AttributeTranslation as AttributeTranslationContract;
use Webkul\Attribute\Models\AttributeTranslation as AttributeTranslationModel;

class AttributeTranslation extends AttributeTranslationModel implements AttributeTranslationContract
{
}
