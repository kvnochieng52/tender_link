<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TendererProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $currentYear = (int) date('Y');

        return [
            'business_name'            => ['required', 'string', 'max:255'],
            'trading_name'             => ['nullable', 'string', 'max:255'],
            'registration_number'      => ['nullable', 'string', 'max:100'],
            'kra_pin'                  => ['nullable', 'string', 'max:50'],
            'business_type'            => ['nullable', 'string', 'max:100'],
            'year_of_registration'     => ['nullable', 'integer', 'min:1900', 'max:' . $currentYear],
            'industry_id'              => ['nullable', 'integer', 'exists:industries,id'],
            'county_id'                => ['nullable', 'integer', 'exists:counties,id'],
            'physical_address'         => ['nullable', 'string', 'max:1000'],
            'postal_address'           => ['nullable', 'string', 'max:255'],
            'website'                  => ['nullable', 'url', 'max:255'],
            'business_description'     => ['nullable', 'string', 'max:2000'],
            'contact_person_name'      => ['required', 'string', 'max:255'],
            'contact_person_position'  => ['nullable', 'string', 'max:100'],
            'contact_phone'            => ['nullable', 'string', 'max:30'],
            'contact_email'            => ['required', 'email', 'max:255'],
        ];
    }
}
