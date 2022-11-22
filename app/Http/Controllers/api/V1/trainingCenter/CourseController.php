<?php

namespace App\Http\Controllers\api\V1\trainingCenter;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\V1\CourseResource;
use App\Models\Category;
use App\Models\Course;
use App\Models\History;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Section;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    public function createCourse(CourseRequest $request)
    {
        if (User::isModerator(auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => __("messages.forbidden"),
            ], 403)->header('Status-Code', 403);
        }
        try {
            $model = new Course();
            $model->user_id = auth()->id();
            $hasType = in_array($request['type'], [1, 2, 3, 4]);
            if ($hasType) {
                $model->type = $request['type'];
                $model->status = Course::DRAFT;
                $model->save();
                if ($request['type'] == Course::ONLINE) {
                    Section::create([
                        "title" => "Section 1",
                        "course_id" => $model->id,
                    ]);
                }
                return response(new CourseResource($model))->setStatusCode(200)->header('Status-Code', '200');
            }
            return response()->json([
                'success' => false,
                'message' => __('messages.type-not-found'),
            ])->header('Status-Code', 200);
        } catch (\Exception $e) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode())->header('Status-Code', $e->getCode()));
        }

    }

    public function getTrainerCourses($id)
    {
        $user = User::where("id", $id)->first();
        if ($user && $user->role_id == Role::TRAINER) {
            $courses = Course::where('user_id', $user->id)->get();
            $lang = Language::getLanguage($request["language_code"]??"hy");
            Category::$language =$lang;
            foreach ($courses as $course) {
                if (!empty($course->category_id) ) {
                    $data = Category::with(["translation", "parent"])->find($course->category_id)->toArray();
                    $course["names"] =array_reverse( Course::getNamesArray($data));
                }
            }
            if (count($courses) == 0) {
                return response()->json(['success' => false,
                    'message' => __("messages.trainer-have-not-course")], 200);
            } else {
                return response()->json(['success' => true, 'data' => $courses], 200);
            }
        } else {
            return response()->json(['success' => false, 'data' => __('messages.not_found')], 403);
        }
    }

    public function getUserReview($id){
        $reviews = [];
        $trainer = Trainer::where('user_id', $id)->first();
        if($trainer !== null){
            $user = User::where('id', $id)->first();
            if($user->role_id == 3){
                $courses = Course::where('trainer_id', $trainer['id'])->with('rates')->get();

                foreach ($courses as $item){
                    if(count($item['rates']) != 0){
                        $user = User::where('id', $item['rates'][0]['user_id'])->first();
                        $reviews[] = [
                            'written_by' => $user['first_name'] . ' ' . $user['last_name'],
                            'avatar' => $user['avatar'],
                            'date' => $item['rates'][0]['updated_at'],
                            'rate' => $item['rates'][0]['rate'],
                            'message' => $item['rates'][0]['message']
                        ];
                    }
                }
            }else{
                return response()->json(['success' => false, 'message' => __('messages.forbidden')]);
            }
        }else{
            return response()->json(['success' => false, 'message' => __('messages.user_not_found')]);
        }
        if($reviews == []){
            return response()->json(['success' => false, 'message' => __('messages.trainer-no-review')], 403);
        }
        return \response()->json(['success' => true, 'data' => $reviews]);
    }

    public function updateCourse(CourseRequest $request)
    {
        $isModerator = User::isModerator(auth()->id());
//        try {
          $with =["lessons", "trainer"];
            if($request["type"] == Course::ONLINE){
                $with = ["sections","sections.lessons","sections.quiz"];
            }
            $model = Course::query()->with($with)->find($request["id"]);
            if (!empty($model)) {
                foreach ($request->toArray() as $key => $value) {
                    if ($key == "lessons" || $key == "trainer"|| $key == "type" || $key == 'language_code' ) {
                        continue;
                    }
                    if ($key == "status") {
                        if (($value == Course::APPROVED || $value == Course::DECLINED) && $isModerator) {
                            if ($value == Course::APPROVED) {
                                $this->saveCourseHistory($request["id"]);
                            }
                            $model->status = $value;

                            Notification::create([
                                "user_id" => $model->user_id,
                                "title" => $value == Course::APPROVED ? __("messages.course_approved") : __("messages.received_declined"),
                                "status" => 0,
                                "type" => "system"
                            ]);
                        } elseif ($value == Course::UNDER_REVIEW) {
                            $model->status = $value;
                            $moderator = User::findModerator();
                            if (User::findModerator()) {
                                Notification::create([
                                    "user_id" => $moderator,
                                    "title" => __("messages.received_moderator"),
                                    "status" => 0,
                                    "type" => "system"
                                ]);
                            }
                        } elseif ($value != Course::APPROVED) {
                            $model->status = $value;
                        }
                    }
                    else {
                        if( $key == "requirements"|| $key == "will_learn"){
                            $model->$key = json_encode($value);
                        }else{
                            $model->$key = $value;
                        }

                    }
                }
                if (!empty($request["lessons"]) && ($request["type"] == Course::ONLINE_WEBINAR || $request["type"] == Course::OFFLINE)) {
                    $isFirstLesson = Lesson::query()->where("course_id", $model->id)->exists();
                    if (!$isFirstLesson && empty($request["lessons"][0]["start_time"])) {
                        return response()->json([
                            'success' => false,
                            'message' => __("messages.first_lesson"),
                        ], 401)->header('Status-Code', 401);
                    } else {
                        try {
                            Lesson::query()->where("course_id",$model->id)->delete();
                            foreach ($request["lessons"] as $lesson) {
                                $lesson["title"]=$lesson["title"]??"";
                                Lesson::create($lesson);

                            }
                        } catch (\Exception $e) {
                            throw new HttpResponseException(response()->json([
                                'success' => false,
                                'message' => $e->getMessage(),
                            ], $e->getCode())->header('Status-Code', $e->getCode()));
                        }

                    }
                }
                if (!empty($request["trainer"]) && empty($request["trainer_id"])) {

                    $trainer_id = $this->createTrainer($request["trainer"]);
                    $model->trainer_id = $trainer_id;
                }
                $model->save();
                $course = Course::with($with)->find($model->id);
                return response(new CourseResource($course))->setStatusCode(200)->header('Status-Code', '200');

            } else {
                return response()->json([
                    'success' => false,
                    'message' => __("messages.not_found"),
                ], 404)->header('Status-Code', 404);
            }
//        } catch
//        (\Exception $e) {
//            throw new HttpResponseException(response()->json([
//                'success' => false,
//                'message' => $e->getMessage(),
//            ], $e->getCode())->header('Status-Code', $e->getCode()));
//        }
    }

    public function getCourses(Request $request)
    {
        $courses = Course::query()->with(["lessons", "trainer", 'rates'])->where('status', Course::APPROVED);
        $limit = $request["limit"] ?? 10;
        if (isset($request["category_id"])) {
            $courses->where("category_id", $request["category_id"]);
        }
        if (isset($request["language_code"])) {
            $courses->where("language", Language::getLanguage($request["language_code"]));
        }
        $courses = $courses->paginate($limit);
        foreach ($courses as $course){
            $course->cover_image = $course->cover_image?env("APP_URL")."/".$course->cover_image:null;
        }
        if (count($courses) == 0) {
            $data = [
                'success' => false,
                'data' => __('messages.not_found'),
            ];
            return response($data)
                ->setStatusCode(200)->header('Status-Code', '200');
        }
        return response(new CourseResource($courses))
            ->setStatusCode(200)->header('Status-Code', '200');


    }
    public function getUserCourses(Request $request)
    {
        $courses = Course::query()->with(["lessons", "trainer", 'rates'])->where('user_id', auth()->id())->whereNot("status",Course::DELETED);
        $limit = $request["limit"] ?? 10;
        foreach ($courses as $course){
            $course->cover_image = $course->cover_image?env("APP_URL")."/".$course->cover_image:null;
        }
        $courses = $courses->paginate($limit);
            $data = [
                'success' => true,
                'data' => $courses,
            ];
            return response($data)
                ->setStatusCode(200)->header('Status-Code', '200');


    }

    public function getReviewsByCourseId($id){
        $reviews = Review::query()->where('course_id', $id)->with('user')->get();
        if(count($reviews) == 0){
            return response()->json(['success' => false, 'data' => __('messages.course-no-review')]);
        }
        return response()->json(['success' => true, 'reviews' => $reviews]);
    }

    public function courseByIdForGuest($id){
        $courses = Course::query()->with(["lessons", "trainer"])->find($id);

        $courseStatus= [1=>'Draft', 2=>'Under review', 3=>'Approved', 4=>'Declined', 5=>'Deleted'];
        $courseLanguage = [1=>'Armenian', 2=>'English'];
        $courseLevel = [1=>'All levels', 2=>'Beginners', 3=>'Middle level', 4=>'Advanced'];
        $with =["lessons", "trainer"];
        if($courses->type == Course::ONLINE){
            $with = ["sections","sections.lessons","sections.quiz"];
        }
        $courses = Course::query()->with($with)->find($id);
        if (!is_null($courses)) {
            if (!empty($courses->category_id) ) {
                $lang = Language::getLanguage($request["language_code"]??"en");
                Category::$language =$lang;
                $data = Category::with(["translation", "parent"])->find($courses->category_id)->toArray();
                $courses["categories"] =array_reverse(Course::getNamesArray($data));
            }
            $courses['status'] = $courseStatus[$courses['status']];
            $courses['language'] = $courseLanguage[$courses['language']];
            $courses['level'] = $courseLevel[$courses['level']];
            $courses["trainer"] = $courses['trainer_id']?Trainer::query()->where("id",$courses['trainer_id'])->select(["id","first_name","last_name"])->first():null;
            unset($courses['trainer_id']);
            if($courses['type']== Course::OFFLINE){
                $courses['type'] = "Offline";
                if(count($courses['lessons']) != 0){
                    $courses['start_date_time'] = $courses['lessons'][0]['start_time'];
                }
                unset($courses['lessons']);
            }elseif($courses['type']== Course::ONLINE){
                $courses['type'] = "Online";
                if(isset($courses['start_date_time'])){
                    unset($courses['start_date_time']);
                }
                $quizCount = Quiz::query()->leftJoin("sections","section_id","=","sections.id")->where("course_id",'=', $id)->count();
                $lessonCount = Lesson::query()->where('course_id', '=', $courses['id'])->count();
                $courses['lessons_count']= $lessonCount;
                $courses['quiz_count']= $quizCount;
                unset($courses['lessons']);
                unset($courses['sections']);
                unset($courses['link']);
            }elseif($courses['type']== Course::ONLINE_WEBINAR){
                $courses['type'] = "Online webinar";
                if(count($courses['lessons']) !== 0){
                    $courses['start_date_time'] = $courses['lessons'][0]['start_time'];
                    unset($courses['lessons']);
                }else{
                    $courses['start_date_time'] = null;
                }
            }
            $data = [
                'success' => true,
                'data' => new CourseResource($courses),
            ];
            return response($data)
                ->setStatusCode(200)->header('Status-Code', '200');
        }
        $data = [
            'success' => false,
            'data' => __('messages.not_found'),
        ];
        return response($data)
            ->setStatusCode(200)->header('Status-Code', '200');

    }

    public function getCourse($id)
    {
        $courses = Course::query()->find($id);
        $with =["lessons", "trainer"];
        if($courses->type == Course::ONLINE){
            $with = ["sections","sections.lessons","sections.quiz"];
        }
        $courses = Course::query()->with($with)->find($id);
        if (!is_null($courses)) {
            $data = [
                'success' => true,
                'data' => new CourseResource($courses),
            ];
            return response($data)
                ->setStatusCode(200)->header('Status-Code', '200');
        }
        $data = [
            'success' => false,
            'data' => __('messages.not_found'),
        ];
        return response($data)
            ->setStatusCode(404)->header('Status-Code', '404');

    }

    public function deleteCourse($id)
    {
        if ($id) {
            $query = Course::query();
            if ($query) {
                $course = $query->where('id', $id)->first();
                if ($course != null) {
                    if ($course['user_id'] === Auth::id()) {
                        $course->status = Course::DELETED;
                        $course->save();
                        $data = [
                            'status' => 'success',
                            'message' => __("messages.course_delete"),
                        ];
                        return response($data)
                            ->setStatusCode(200)->header('Status-Code', '200');
                    } else {
                        $data = [
                            'success' => false,
                            'message' => __("messages.forbidden"),
                        ];
                        return response($data)
                            ->setStatusCode(200)->header('Status-Code', '200');
                    }
                } else {
                    $data = [
                        'success' => false,
                        'message' => __("messages.not_found"),
                    ];
                    return response($data)
                        ->setStatusCode(200)->header('Status-Code', '200');
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => "Course not found",
            ], 200)->header('Status-Code', '200');
        }
    }

    public function createSection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => ['integer', 'required', 'exists:courses,id'],
            'title' => ['string', 'max:255'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                "errors" => $validator->errors()
            ], 200)->header('Status-Code', '200');
        }
        try {

            $section = Section::create([
                "title" => $request["title"] ?? "Section title",
                "course_id" => $request['course_id']
            ]);
            return response($section)->setStatusCode(200)->header('Status-Code', '200');

        } catch
        (\Exception $e) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500)->header('Status-Code', 401));
        }
    }

    public function updateSection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['integer', 'required', 'exists:sections,id'],
            'title' => ['string', 'max:255'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                "errors" => $validator->errors()
            ], 200)->header('Status-Code', '401');
        }
        $section = Section::query()->find($request["id"]);
        try {
            $section->title = $request["title"];
            return response($section)->setStatusCode(200)->header('Status-Code', '200');

        } catch
        (\Exception $e) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500)->header('Status-Code', 401));
        }
    }

    public function deleteSection(Request $request)
    {
        if (!empty($request["id"])) {
            $section = Section::query()->find($request["id"]);
            if ($section) {
                $course = Course::where('id', $section['course_id'])->where('user_id', Auth::id())->first();
                if ($course) {
                    $section->delete();
                    $data = [
                        'success' => true,
                        'message' => __("messages.deleted"),
                    ];
                    return response($data)
                        ->setStatusCode(200)->header('Status-Code', '200');
                } else {
                    dd(1);
                }

            } else {
                return response()->json([
                    'success' => false,
                    'message' =>  __("messages.not_found"),
                ], 200)->header('Status-Code', '200');
            }
        }
        return response()->json(['success' => false, 'message' => 'Insert id']);
    }

    private function createTrainer($data)
    {
        try {
            $trainer = new Trainer();
            $trainer->first_name = $data["first_name"] ?? null;
            $trainer->last_name = $data["last_name"] ?? null;
            $trainer->bio = $data["bio"] ?? null;
            $trainer->user_id = auth()->id();
            $trainer->avatar = $data["avatar"] ?? null;

            $trainer->save();
            return $trainer->id;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode())->header('Status-Code', 401);
        }
    }

    private function saveCourseHistory($id)
    {
        $model = Course::query()->with(["lessons", "trainer"])->find($id);
        $history = History::query()->where("course_id", $id)->where("user_id", auth()->id())->first();
        if ($history) {
            $history->course_id = $id;
            $history->user_id->auth()->id();
            $history->old_value->json_encode($model);
        } else {
            History::create([
                "course_id" => $id,
                "user_id" => auth()->id(),
                "old_value" => json_encode($model)
            ]);
        }
    }
}
