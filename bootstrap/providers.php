<?php

use App\Providers\AppServiceProvider;
use App\Providers\BladeDirectiveServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\VoltServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    VoltServiceProvider::class,
    BladeDirectiveServiceProvider::class,
];
