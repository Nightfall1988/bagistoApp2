<?php

namespace Hitexis\CartRule\Models;

use Illuminate\Database\Eloquent\Model;
use Hitexis\CartRule\Contracts\CartRuleTranslation as CartRuleTranslationContract;

class CartRuleTranslation extends Model implements CartRuleTranslationContract
{
    public $timestamps = false;

    protected $fillable = ['label'];
}
