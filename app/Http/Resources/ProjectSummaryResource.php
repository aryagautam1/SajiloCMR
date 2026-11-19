<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectSummaryResource extends JsonResource
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
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'priority' => $this->priority,
            'priority_label' => $this->priorityLabel(),
            'progress' => (int) $this->progress,
            'start_date' => $this->start_date?->toDateString(),
            'expected_completion_date' => $this->expected_completion_date?->toDateString(),
            'is_overdue' => $this->isOverdue(),
            'budget' => $this->budget !== null ? (float) $this->budget : null,
            'budget_formatted' => $this->budget !== null
                ? config('crm.currency').' '.number_format((float) $this->budget)
                : null,
            'client' => new ClientSummaryResource($this->whenLoaded('client')),
            'assigned_to' => new UserSummaryResource($this->whenLoaded('assignedTo')),
        ];
    }
}
