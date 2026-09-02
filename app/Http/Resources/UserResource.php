<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
 */
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => (bool) $user->is_active,
            'roles' => $this->whenLoaded('roles', fn () => $user->roles->pluck('name')),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
