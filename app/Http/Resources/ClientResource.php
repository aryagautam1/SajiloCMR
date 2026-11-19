<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'notes' => $this->notes,
            'logo_url' => $this->logo_path ? Storage::disk('uploads')->url($this->logo_path) : null,
            'initials' => $this->initials(),
            'projects_count' => $this->whenCounted('projects_count'),
            'projects' => ProjectSummaryResource::collection($this->whenLoaded('projects')),
            'creator' => new UserSummaryResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
