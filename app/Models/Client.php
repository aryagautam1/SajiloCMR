<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'status',
        'notes',
        'logo_path',
        'created_by',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->company_name ?: $this->name)) ?: [];

        return strtoupper(collect($parts)->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    }

    public function statusLabel(): string
    {
        return config("crm.client_statuses.{$this->status}.label", ucfirst($this->status));
    }
}
