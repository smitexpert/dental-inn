<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use Database\Factories\NotificationTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $key
 * @property NotificationChannel $channel
 * @property ?string $subject
 * @property string $body
 * @property bool $is_active
 * @property string $locale
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class NotificationTemplate extends Model
{
    /** @use HasFactory<NotificationTemplateFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'key',
        'channel',
        'subject',
        'body',
        'is_active',
        'locale',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'channel', 'subject', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @param  Builder<NotificationTemplate>  $query
     * @return Builder<NotificationTemplate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<NotificationTemplate>  $query
     * @return Builder<NotificationTemplate>
     */
    public function scopeForKey(Builder $query, string $key, ?NotificationChannel $channel = null, string $locale = 'en'): Builder
    {
        $query = $query->where('key', $key)->where('locale', $locale);

        if ($channel !== null) {
            $query->where('channel', $channel->value);
        }

        return $query;
    }
}
