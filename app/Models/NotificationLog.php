<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Database\Factories\NotificationLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RuntimeException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property ?int $template_id
 * @property NotificationChannel $channel
 * @property string $recipient
 * @property ?string $subject
 * @property string $body_rendered
 * @property NotificationStatus $status
 * @property ?string $error_message
 * @property ?string $reference_type
 * @property ?int $reference_id
 * @property ?Carbon $sent_at
 * @property Carbon $created_at
 * @property-read ?NotificationTemplate $template
 * @property-read ?Model $reference
 */
class NotificationLog extends Model
{
    /** @use HasFactory<NotificationLogFactory> */
    use HasFactory, LogsActivity;

    public $timestamps = false;

    protected $fillable = [
        'template_id',
        'channel',
        'recipient',
        'subject',
        'body_rendered',
        'status',
        'error_message',
        'reference_type',
        'reference_id',
        'sent_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => NotificationStatus::class,
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('NotificationLog rows are immutable.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('NotificationLog rows are immutable.');
        });
    }

    /** @return BelongsTo<NotificationTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Activitylog records creation only (immutable log).
        return LogOptions::defaults()
            ->logOnly(['channel', 'recipient', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @param  Builder<NotificationLog>  $query
     * @return Builder<NotificationLog>
     */
    public function scopeForChannel(Builder $query, NotificationChannel $channel): Builder
    {
        return $query->where('channel', $channel->value);
    }

    /**
     * @param  Builder<NotificationLog>  $query
     * @return Builder<NotificationLog>
     */
    public function scopeForStatus(Builder $query, NotificationStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * @param  Builder<NotificationLog>  $query
     * @return Builder<NotificationLog>
     */
    public function scopeForTemplate(Builder $query, int $templateId): Builder
    {
        return $query->where('template_id', $templateId);
    }

    /**
     * @param  Builder<NotificationLog>  $query
     * @return Builder<NotificationLog>
     */
    public function scopeForReference(Builder $query, string $type, int $id): Builder
    {
        return $query->where('reference_type', $type)
            ->where('reference_id', $id);
    }

    /**
     * @param  Builder<NotificationLog>  $query
     * @return Builder<NotificationLog>
     */
    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end);
    }
}
