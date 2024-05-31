<?php

require __DIR__ . '/vendor/autoload.php';

use Hitexis\Wholesale\Http\Requests\WholesaleRequest;

$request = new WholesaleRequest();

echo 'WholesaleRequest class exists and is autoloaded correctly.';
