<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
    * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'ebay_url' => ['required', 'url', 'max:2048',
                Rule::unique('listings', 'ebay_url')->where('user_id', $this->user()->id),
            ],
            'amazon_url' => ['nullable', 'url', 'max:2048'],
            'ebay_price' => ['required', 'numeric', 'min:0'],
            'amazon_price' => ['required', 'numeric', 'min:0'],
            'ebay_fee' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:40'],
            'listed_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
