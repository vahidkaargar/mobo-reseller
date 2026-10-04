<?php

namespace App\Enums;

use App\Services\Catalog\Contracts\CatalogInterface;
use App\Services\Catalog\Suppliers\BambooCatalog;

enum SuppliersEnum: string
{
    case BAMBOO = 'BAMBOO';

    public function catalog(): CatalogInterface
    {
        return match ($this) {
            self::BAMBOO => new BambooCatalog,
        };
    }
}
