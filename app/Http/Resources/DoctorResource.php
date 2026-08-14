<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Doctor
 */
class DoctorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => (bool) $user->is_active,
            'specialization' => $this->specialization,
            'license_number' => $this->license_number,
            'bio' => $this->bio,
            'years_of_experience' => $this->years_of_experience,
            'consultation_fee' => $this->consultation_fee !== null ? (float) $this->consultation_fee : null,
            'color' => $this->color,
            'is_available' => (bool) $this->is_available,
        ];
    }
}
