<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeStatusFilter(Builder $query, ?string $status): Builder
    {
        if ($status && in_array($status, ['pending', 'in_progress', 'completed'])) {
            return $query->where('status', $status);
        }
        
        return $query;
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query; 
        }
        
        return $query->where('user_id', $user->id); 
    }

    /**
     * Get task counts by status (optimized query)
     */
    public static function getCountsByStatus(int $userId): array
    {
        $counts = self::where('user_id', $userId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending')
            ->selectRaw('SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress')
            ->selectRaw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            ->first();

        return [
            'total' => $counts->total ?? 0,
            'pending' => $counts->pending ?? 0,
            'in_progress' => $counts->in_progress ?? 0,
            'completed' => $counts->completed ?? 0,
        ];
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = strip_tags($value);
    }

    /**
     * Set the title with XSS protection
     */
    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = strip_tags($value);
    }
}