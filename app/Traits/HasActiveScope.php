<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;


trait HasActiveScope
{
    
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', $this->activeStatuses());
    }

    
    public function activeStatuses(): array
    {
        return property_exists($this, 'activeStatuses')
            ? $this->activeStatuses 
            : [];
    }
}
