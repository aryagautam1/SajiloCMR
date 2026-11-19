<?php

namespace Database\Seeders\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

trait SetsCreatedAt
{
    /**
     * Seeded records need believable historic timestamps. created_at is not
     * mass assignable, so it is written explicitly after the record is saved.
     */
    protected function setCreatedAt(Model $model, CarbonInterface $date): void
    {
        $timestamps = $model->timestamps;
        $model->timestamps = false;
        $model->forceFill(['created_at' => $date, 'updated_at' => $date])->save();
        $model->timestamps = $timestamps;
    }
}
