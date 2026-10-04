<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

arch('no debugging calls are left in the code')
    ->expect(['dd', 'dump', 'var_dump', 'ray', 'die'])
    ->not->toBeUsed();

arch('env() is only read in config files')
    ->expect('env')
    ->toOnlyBeUsedIn('config');

arch('models extend Eloquent')
    ->expect('App\Models')
    ->toExtend(Model::class);

arch('App\Enums only contains enums')
    ->expect('App\Enums')
    ->toBeEnums();

arch('services do not depend on the HTTP or Livewire layer')
    ->expect('App\Services')
    ->not->toUse(['App\Http\Controllers', 'App\Http\Requests', 'App\Http\Middleware', 'Livewire']);

arch('controllers are suffixed')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('form requests extend FormRequest')
    ->expect('App\Http\Requests')
    ->toExtend(FormRequest::class)
    ->toHaveSuffix('Request');
