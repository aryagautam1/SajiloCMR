<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'assigned_to',
        'project_name',
        'description',
        'status',
        'priority',
        'start_date',
        'expected_completion_date',
        'budget',
        'progress',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_completion_date' => 'date',
            'budget' => 'decimal:2',
            'progress' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function code(): string
    {
        return 'PRJ-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function isOverdue(): bool
    {
        return $this->expected_completion_date !== null
            && ! in_array($this->status, ['completed', 'cancelled'], true)
            && $this->expected_completion_date->isPast();
    }

    public function statusLabel(): string
    {
        return config("crm.project_statuses.{$this->status}.label", ucfirst($this->status));
    }

    public function priorityLabel(): string
    {
        return config("crm.priorities.{$this->priority}.label", ucfirst($this->priority));
    }
}
