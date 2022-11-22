<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CategoryRequest extends FormRequest
{
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
            'categories' => ['required'],
            'categories.*.ordering' => ['required',"integer",],
            'categories.*.icon' => ['mimes:jpg,png', 'max:2048'],
            'categories.*.parent_id' => ['required',"integer",],
            'categories.*.category_info' => ['required', 'array'],
            'categories.*.category_info.*.title' => ['required', 'string', 'max:255'],
            'categories.*.category_info.*.language_code' => ['required',"string",]
        ];

    }

    public function failedValidation(Validator $validator)
    {
        $messages = GettingErrorMessages::gettingMessage($validator->errors()->messages());
        throw new HttpResponseException(response()->json([

            'success' => false,
            'message' => __('messages.validation_errors'),
            'errors' => $messages
        ])->header('Status-Code', 200));
    }
}

