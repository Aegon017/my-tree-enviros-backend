<?php

declare(strict_types=1);

namespace App\Rules;

use Exception;
use Illuminate\Contracts\Validation\Rule;
use libphonenumber\PhoneNumberUtil;

final readonly class PhoneNumberRule implements Rule
{
    public function __construct(
        private ?string $countryCode = null
    ) {}

    public function passes($attribute, $value): bool
    {
        if (in_array($this->countryCode, [null, '', '0'], true)) {
            return false;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $parsedNumber = $phoneUtil->parse($value, $this->getCountryIso());

            return $phoneUtil->isValidNumber($parsedNumber);
        } catch (Exception) {
            return false;
        }
    }

    public function message(): string
    {
        return 'The phone number is invalid for the selected country.';
    }

    private function getCountryIso(): ?string
    {
        if (in_array($this->countryCode, [null, '', '0'], true) || ! str_starts_with($this->countryCode, '+')) {
            return null;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $countryCode = (int) mb_ltrim($this->countryCode, '+');

            return in_array($phoneUtil->getRegionCodeForCountryCode($countryCode), ['', '0'], true) ? null : $phoneUtil->getRegionCodeForCountryCode($countryCode);
        } catch (Exception) {
            return null;
        }
    }
}
