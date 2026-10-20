<?php

declare(strict_types=1);

namespace App\Http\Requests\Patients\Concerns;

/**
 * Patient write paths collect an age ("20 years old") rather than a date of
 * birth, because reception staff rarely know the exact birth date. The
 * patients table has no age column, so the entered age is converted to an
 * approximate `date_of_birth` before validation runs.
 */
trait DerivesDateOfBirthFromAge
{
    /** Shared age bounds for every patient write path. */
    public const AGE_MIN = 0;

    public const AGE_MAX = 130;

    /**
     * Convert an entered `age` into the approximate `date_of_birth` the
     * patients table stores.
     *
     * Precedence: a present `age` always wins. A blank or absent `age`
     * returns early and leaves `date_of_birth` untouched, so an edit that
     * doesn't touch the age field can't wipe an existing date of birth.
     *
     * Only `date_of_birth` is merged. `age` is deliberately left in place —
     * this runs from prepareForValidation(), i.e. *before* the `integer` and
     * `max` rules are evaluated, so clearing it here would make every age
     * validation pass vacuously. It is stripped from the payload in
     * PatientController instead.
     */
    protected function applyAgeToDateOfBirth(): void
    {
        $age = $this->input('age');

        if ($age === null || $age === '') {
            return;
        }

        $this->merge(['date_of_birth' => now()->subYears((int) $age)->toDateString()]);
    }

    /**
     * @return array<int, string>
     */
    protected function ageRules(): array
    {
        return ['nullable', 'integer', 'min:'.self::AGE_MIN, 'max:'.self::AGE_MAX];
    }
}
