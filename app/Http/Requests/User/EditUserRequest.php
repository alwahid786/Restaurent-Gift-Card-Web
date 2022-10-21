<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Traits\ResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\EmailValidationRule;
use Auth;

class EditUserRequest extends FormRequest
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
        $id = auth()->user()->id;
        return [
            'name' => ['string', 'max:255', 'unique:users,name' . auth()->user()->id],
            'email' => ['unique:users,email' . auth()->user()->id, new EmailValidationRule],
            'phone' => ['string', 'unique:users,phone' . auth()->user()->id],
            'profile_image' => 'file',
            'address' => 'string',
        ];
    }

    // Update validation errors response 
    protected function failedValidation(Validator $validator)
    {
        $errors = $this->sendError(implode(",", $validator->errors()->all()));
        throw new HttpResponseException($errors, 422);
    }
}
