<?php

namespace App\Http\Controllers\api\V1\trainer;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CourseResource;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class TrainerController extends Controller
{
    public function getUserTrainers()
    {
        if (Auth::check()) {
            if (User::isTrainerOrTrainingCenter(auth()->id())) {
                $trainers = Trainer::query()->where("user_id", auth()->id())->get();
                return response(new CourseResource($trainers))->setStatusCode(200)->header('Status-Code', '200');

            } else {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' =>__("messages.forbidden"),
                ], 403)->header('Status-Code', '403'));
            }
        } else {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => __("messages.forbidden"),
            ], 403)->header('Status-Code', '403'));
        }
    }

}
