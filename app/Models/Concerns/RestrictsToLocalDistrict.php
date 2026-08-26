<?php

namespace App\Models\Concerns;

use App\Support\LocalDistrictAccess;
use Illuminate\Database\Eloquent\Builder;

trait RestrictsToLocalDistrict
{
    protected static function bootRestrictsToLocalDistrict(): void
    {
        static::addGlobalScope('local_district', function (Builder $builder) {
            $district = LocalDistrictAccess::assigned();

            if ($district !== null) {
                $builder->where(
                    $builder->getModel()->qualifyColumn('distrito_local'),
                    $district
                );
            }
        });
    }
}
