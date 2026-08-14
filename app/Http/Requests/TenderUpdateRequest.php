<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

class TenderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        try {
            $tenderId = Crypt::decryptString($this->route('encryptedId'));
        } catch (\Throwable) {
            $tenderId = 0;
        }

        return [
            'title'                  => ['required', 'string', 'max:255'],
            'tender_no'              => ['required', 'string', 'max:100', "unique:tenders,tender_no,{$tenderId}"],
            'create_new_institution' => ['nullable', 'boolean'],
            'edit_institution'       => ['nullable', 'boolean'],
            'institution_id'         => ['required_unless:create_new_institution,1', 'nullable', 'exists:institutions,id'],
            'institution_name'       => ['required_if:create_new_institution,1', 'nullable', 'string', 'max:255'],
            'institution_type_id'    => ['required_if:create_new_institution,1', 'nullable', 'exists:institution_types,id'],
            'institution_email'      => ['nullable', 'email', 'max:255'],
            'institution_telephone'  => ['nullable', 'string', 'max:20'],
            'institution_logo'       => ['nullable', 'image', 'max:3072'],
            'institution_profile'    => ['nullable', 'string'],
            'institution_website'    => ['nullable', 'url', 'max:255'],
            'institution_address'    => ['nullable', 'string', 'max:255'],
            'industry_id'            => ['required', 'exists:industries,id'],
            'county_id'              => ['required', 'exists:counties,id'],
            'closing_date_and_time'  => ['required', 'date'],
            'expiry_date'            => ['required', 'date'],
            'tender_status_id'       => ['nullable', 'exists:tender_statuses,id'],
            'tender_link_process'    => ['nullable', 'boolean'],
            'tender_fee_amount'      => ['nullable', 'numeric', 'min:0'],
            'description'            => ['required', 'string'],
            'key_requirements'       => ['nullable', 'string'],
            'requirements'           => ['nullable', 'array'],
            'requirements.*.title'   => ['required_with:requirements', 'string', 'max:255'],
            'requirements.*.notes'   => ['nullable', 'string'],
            'requirements.*.mandatory' => ['nullable', 'boolean'],

            'categories'             => ['nullable', 'array'],
            'categories.*.id'        => ['nullable', 'integer', 'exists:tender_categories,id'],
            'categories.*.tender_no' => ['required_with:categories', 'string', 'max:100'],
            'categories.*.title'     => ['required_with:categories', 'string', 'max:500'],

            'files'             => ['nullable', 'array'],
            'files.*'           => ['nullable', 'file', 'max:10240'],
            'remove_file_ids'   => ['nullable', 'array'],
            'remove_file_ids.*' => ['nullable', 'integer', 'exists:tender_files,id'],

            'advert_file'                       => ['nullable', 'file', 'max:10240'],
            'self_declaration_file'             => ['nullable', 'file', 'max:10240'],
            'confidential_questionnaire_file'   => ['nullable', 'file', 'max:10240'],
            'remove_advert'                     => ['nullable', 'boolean'],
            'remove_self_declaration'           => ['nullable', 'boolean'],
            'remove_confidential_questionnaire' => ['nullable', 'boolean'],
        ];
    }
}
