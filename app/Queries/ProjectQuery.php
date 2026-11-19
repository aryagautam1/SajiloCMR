<?php

namespace App\Queries;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

class ProjectQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public static function build(array $filters): Builder
    {
        $query = Project::query()->with(['client', 'assignedTo']);

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $term = '%'.$search.'%';
                $builder->where('project_name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('client', function (Builder $clientQuery) use ($term) {
                        $clientQuery->where('name', 'like', $term)
                            ->orWhere('company_name', 'like', $term);
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (! empty($filters['overdue']) && filter_var($filters['overdue'], FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNotNull('expected_completion_date')
                ->whereDate('expected_completion_date', '<', now()->toDateString())
                ->whereNotIn('status', ['completed', 'cancelled']);
        }

        $sortable = ['project_name', 'status', 'priority', 'progress', 'start_date', 'expected_completion_date', 'created_at'];
        $sort = in_array($filters['sort'] ?? null, $sortable, true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', 'desc');
    }
}
