<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CourseRequest extends FormRequest
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


        $rules = [
            'type' => ['required'],
            'cover_image' => ["nullable"],
            'promo_video' => ["nullable"],
            'title' => ['string', 'max:255',"nullable"],
            'sub_title' => ['string', 'max:255',"nullable"],
            'language' => ["integer", "exists:languages,id","nullable"],
            'status' => ["integer","nullable"],
            'category_id' => ["integer", "exists:categories,id","nullable"],
            'max_participants' => ["integer","nullable"],
            'level' => ["integer","nullable"],
            'trainer_id' => ["integer", "exists:trainers,id","nullable"],
            'price' => ['regex:/^\d+(\.\d{1,2})?$/',"nullable"],
            'address' => ["nullable",'max:255'],
            'requirements' => ["string"],
            'will_learn' => ["string"],
            'currency' => ['string',"nullable"],
            'lessons' => ['array',"nullable"],
            'lessons.*.title' => ['string', 'max:255',"nullable"],
            "lessons.*.duration" => ["integer","nullable"],
            'lessons.*.start_time' => ["date_format:Y-m-d H:i:s","nullable"],
            'lessons.*.course_id' => [ "integer","nullable"],
            'trainer.first_name' => [ 'string', 'max:255',"nullable"],
            'trainer.last_name' => [ 'string', 'max:255',"nullable"],
            'trainer.bio' => ['string',"nullable"],
            'trainer.avatar' => [ 'string',"nullable"],
        ];
        $data = $this->request->all();
        if (!empty($data["status"]) && $data["status"] == Course::APPROVED) {
            foreach ($rules as $key => $value) {
                if ($key != "type") {
                    $rules[$key][] = "required";
                }

            }
        }
        return $rules;
    }

    public function failedValidation(Validator $validator)
    {
        $messages = GettingErrorMessages::gettingMessage($validator->errors()->messages());
        throw new HttpResponseException(response()->json([

            'success' => false,
            'message' =>  __('messages.validation_errors'),
            'errors' => $messages
        ])->header('Status-Code', 200));
    }
}
