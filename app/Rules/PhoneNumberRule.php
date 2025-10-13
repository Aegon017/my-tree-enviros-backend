<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberRule implements Rule
{
    public function __construct(
        private ?string $countryCode = null
    ) {}

    public function passes($attribute, $value): bool
    {
        if (!$this->countryCode) {
            return false;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $parsedNumber = $phoneUtil->parse($value, $this->getCountryIso());

            return $phoneUtil->isValidNumber($parsedNumber);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function message(): string
    {
        return 'The phone number is invalid for the selected country.';
    }

    private function getCountryIso(): ?string
    {
        if (!$this->countryCode || !str_starts_with($this->countryCode, '+')) {
            return null;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $countryCode = (int) ltrim($this->countryCode, '+');
            return $phoneUtil->getRegionCodeForCountryCode($countryCode) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}