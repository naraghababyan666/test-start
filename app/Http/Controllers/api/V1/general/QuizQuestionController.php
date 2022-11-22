<?php

namespace App\Http\Controllers\api\V1\general;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;

class QuizQuestionController extends Controller
{



    public function store(QuestionRequest $request)
    {
        $data = $request->all();
        $validated = $request->validated();
        if(array_key_exists('quiz_id', $data)){
            $newQuiz = QuizQuestion::create([
                'quiz_id' => $data['quiz_id'],
                'question' => $validated['question'],
                'answers' => $validated['answers'],
                'right_answers' => $validated['right_answers']
            ]);
        }else{
            $quiz = Quiz::create([
                'section_id' => $data['section_id'],
                "position"=>$data['position']??0
            ]);
            $newQuiz = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $validated['question'],
                'answers' => $validated['answers'],
                'right_answers' => $validated['right_answers']
            ]);
        }
        return response()->json(['quiz' => $newQuiz]);
    }

    public function updateQuizQuestion(QuestionRequest $request, $id){
        $question = QuizQuestion::find($id);
        if($question){
            $question->update($request->validated());
            $question->save();
            return response()->json(['message' => __("messages.question-update")], 200);
        }
        return response()->json(['message' => __("messages.question-not-found")], 200);
    }

    public function getQuizQuestionById($id){
        $question = QuizQuestion::find($id);
        if($question){
            return response()->json(['question' => $question], 200);
        }
        return response()->json(['message' => __("messages.question-not-found")]);
    }

    public function deleteQuizQuestion($id){
        QuizQuestion::destroy($id);
        return response()->json(['message' => 'messages.question-delete'], 200);
    }

}
