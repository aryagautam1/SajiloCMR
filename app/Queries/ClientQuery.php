<?php

namespace App\Queries;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;

class ClientQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public static function build(array $filters): Builder
    {
        $query = Client::query()->withCount('projects');

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $term = '%'.$search.'%';
                $builder->where('name', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortable = ['name', 'company_name', 'email', 'status', 'projects_count', 'created_at'];
        $sort = in_array($filters['sort'] ?? null, $sortable, true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', 'desc');
    }
}
