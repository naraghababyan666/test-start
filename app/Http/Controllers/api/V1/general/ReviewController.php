<?php

namespace App\Http\Controllers\api\V1\general;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function setRateCourse(Request $request){
        $validator  = Validator::make($request->all(), [
            'user_id' => 'required',
            'course_id' => 'required',
            'rate' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                "errors" => $validator->errors()
            ])->header('Status-Code', 200);
        }
        $data = $validator->validated();
        $rating = Review::where('user_id', $data['user_id'])->where('course_id', $data['course_id'])->first();
        if(empty($rating)){
            Review::create([
                'user_id' => Auth::id(),
                'course_id' => $data['course_id'],
                'rate' => $data['rate'],
                'message' => $data['message']
            ]);
            return response()->json([
                'message' => __('messages.rate-added'),
            ], 200);
        }else{
            Review::where('user_id', $data['user_id'])->where('course_id', $data['course_id'])->update([
                'rate' => $data['rate']
            ]);
            return response()->json([
                'message' => __('messages.rate-update'),
            ], 200);
        }
    }

    public function removeRateCourse(Request $request){
        $validator  = Validator::make($request->all(), [
            'user_id' => 'required',
            'course_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                "errors" => $validator->errors()
            ])->header('Status-Code', 200);
        }
        $data = $validator->validated();
        Review::where('user_id', $data['user_id'])->where('course_id', $data['course_id'])->delete();
        return response()->json([
            'message' => __('messages.rate-delete'),
        ], 200);
    }
}
