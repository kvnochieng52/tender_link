<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenderStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'tender_no' => ['required', 'string', 'max:100', 'unique:tenders,tender_no'],
            'industry_id' => ['required', 'exists:industries,id'],
            'county_id' => ['required', 'exists:counties,id'],
            'closing_date_and_time' => ['required', 'date', 'after:now'],
            'expiry_date' => ['required', 'date'],
            'description' => ['required', 'string'],
            'key_requirements' => ['nullable', 'string'],
            'tender_link_process' => ['nullable', 'boolean'],
            'tender_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'requirements' => ['nullable', 'array'],
            'requirements.*.title' => ['required_with:requirements', 'string', 'max:255'],
            'requirements.*.notes' => ['nullable', 'string'],
            'requirements.*.mandatory' => ['nullable', 'boolean'],

            'create_new_institution' => ['nullable', 'boolean'],
            'edit_institution' => ['nullable', 'boolean'],
            'institution_id' => ['required_unless:create_new_institution,1', 'nullable', 'exists:institutions,id'],

            'institution_name' => ['required_if:create_new_institution,1', 'required_if:edit_institution,1', 'nullable', 'string', 'max:255'],
            'institution_type_id' => ['required_if:create_new_institution,1', 'required_if:edit_institution,1', 'nullable', 'exists:institution_types,id'],
            'institution_email' => ['nullable', 'email', 'max:255'],
            'institution_telephone' => ['nullable', 'string', 'max:20'],
            'institution_logo' => ['nullable', 'image', 'max:3072'],
            'institution_profile' => ['nullable', 'string'],
            'institution_website' => ['nullable', 'url', 'max:255'],
            'institution_address' => ['nullable', 'string', 'max:255'],

            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:10240'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'institution_id.required_unless' => 'Please select an institution or create a new one.',
            'institution_name.required_if' => 'Institution name is required when creating a new institution.',
            'institution_type_id.required_if' => 'Institution type is required when creating a new institution.',
            'closing_date_and_time.after' => 'Closing date and time must be in the future.',
            'expiry_date.required' => 'Expiry date and time is required.',
            'files.required' => 'Please upload at least one tender file.',
            'files.min' => 'Please upload at least one tender file.',
        ];
    }
}
