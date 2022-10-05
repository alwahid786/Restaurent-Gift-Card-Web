<?php

namespace App\Http\Requests\Restaurent;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Traits\ResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GiftIdRequest extends FormRequest
{
    use ResponseTrait;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'token_id' => 'required|exists:qr_codes,id',
        ];
    }

    // Update validation errors response 
    protected function failedValidation(Validator $validator)
    {
        $errors = $this->sendError(implode(",", $validator->errors()->all()));
        throw new HttpResponseException($errors, 422);
    }
}
