<?php

namespace App\Http\Controllers\api\V1\trainingCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\SectionLesson;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

class LessonController extends Controller
{
    public function getLessons()
    {
        $lessons = Lesson::query()->select(["id", "title", "article", "video_url", "type"])->where("type", "course")->get();
        return response($lessons)->setStatusCode(200)->header('Status-Code', '200');

    }

    public function getSectionLessons($id)
    {
        $lessons = Lesson::query()->with("section")->select(["id", "title", "article", "video_url", "type"])->whereHas('section', function ($q) use ($id) {
            $q->where('section_id', $id);
        })->get();
        return response($lessons)->setStatusCode(200)->header('Status-Code', '200');

    }

    public function getLesson($id)
    {
        $lesson = Lesson::query()->find($id);
        if ($lesson) {
            return response($lesson)->setStatusCode(200)->header('Status-Code', '200');
        }
        return response()->json([
            'success' => false,
            'message' => __("messages.not_found"),
        ], 200)->header('Status-Code', 200);
    }

    public function create(LessonRequest $request)
    {
        $section = Section::query()->where("course_id", $request["course_id"])->where("id", $request["section_id"])->first();

        if ($section) {
            try {
                $lesson = Lesson::create($request->all());
                $lesson->type = "course";
                if ($lesson->save()) {
                    SectionLesson::create([
                        "section_id" => $request["section_id"],
                        "lesson_id" => $lesson->id
                    ]);
                    return response($lesson)->setStatusCode(200)->header('Status-Code', '200');

                }
            } catch
            (\Exception $e) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ],  $e->getCode())->header('Status-Code', $e->getCode()));
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => __("messages.not_found"),
            ], 200)->header('Status-Code', 200);
        }
    }

    public function update(LessonRequest $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => ['integer', 'required'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                "errors" => $validator->errors()
            ], 200)->header('Status-Code', 200);
        }
        $lesson = Lesson::query()->find($request['id']);
        if ($lesson) {
            try {
                $lesson->title = $request["title"] ?? $lesson->title;
                $lesson->article = $request["article"] ?? $lesson->article;
                $lesson->video_url = $request["video_url"] ?? $lesson->video_url;
                $lesson->position = $request["position"] ?? $lesson->position;
                $lesson->save();
                return response($lesson)->setStatusCode(200)->header('Status-Code', '200');

            } catch
            (\Exception $e) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->getCode())->header('Status-Code', $e->getCode()));
            }
        } else {
            response()->json([
                'success' => false,
                'message' => __("messages.not_found"),
            ], 200)->header('Status-Code', 200);
        }
    }

    public function delete(LessonRequest $request)

    {
        $lesson = Lesson::query()->find($request["id"]);
        if ($lesson) {
            try {
                $lesson->delete();
                $data = [
                    'success' => true,
                    'message' => __("messages.deleted"),
                ];
                return response($data)
                    ->setStatusCode(200)->header('Status-Code', '200');
            } catch
            (\Exception $e) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->getCode())->header('Status-Code', $e->getCode()));
            }
        } else {
            response()->json([
                'success' => false,
                'message' => __("messages.not_found"),
            ])->header('Status-Code', 200);
        }
    }
}
