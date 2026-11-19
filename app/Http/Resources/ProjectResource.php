<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code(),
            'project_name' => $this->project_name,
            'description' => $this->description,
            'client_id' => $this->client_id,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'priority' => $this->priority,
            'priority_label' => $this->priorityLabel(),
            'start_date' => $this->start_date?->toDateString(),
            'expected_completion_date' => $this->expected_completion_date?->toDateString(),
            'budget' => $this->budget !== null ? (float) $this->budget : null,
            'budget_formatted' => $this->budget !== null
                ? config('crm.currency').' '.number_format((float) $this->budget, 2)
                : null,
            'progress' => (int) $this->progress,
            'is_overdue' => $this->isOverdue(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'client' => new ClientSummaryResource($this->whenLoaded('client')),
            'assigned_to' => new UserSummaryResource($this->whenLoaded('assignedTo')),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
