<?php

namespace App\Http\Controllers\api\V1\general;

use App\Http\Controllers\Controller;
use App\Http\Requests\BasketListsRequest;
use App\Models\BasketList;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Course;
use App\Models\Trainer;
use App\Models\WishList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BasketListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */


    public function index()
    {
        $basketList = BasketList::query()->where('user_id', Auth::id())->with('courses')->get();
        if(count($basketList) != 0){
            foreach ($basketList as $item){
                $item['title'] = $item['courses']['title'];
                $item['sub_title'] = $item['courses']['sub_title'];
                $item['price'] = $item['courses']['price'];
                $item['currency'] = $item['courses']['currency'] ?? 'AMD';
                $item['rate'] = $item['courses']['rate'] ?? 5;
                if($item['courses']){
                    if($item['courses']['type'] == 1){
                        $item['type'] = Course::getType($item['courses']['type']);
                    }
                    $currentCategory = Category::where('id', $item['courses']['category_id'])->first();
                    if($currentCategory){
                        $currentCategoryTitle = CategoryTranslation::where('category_id', $item['courses']['category_id'])->first()['title'];
                        $item['category_title'] = $currentCategoryTitle;
                        if($currentCategory['parent_id'] != null){
                            $currentCategoryParentTitle = CategoryTranslation::where('category_id', $currentCategory['parent_id'])->first()['title'];
                            $item['category_parent_title'] = $currentCategoryParentTitle;
                        }
                    }
                    if($item['courses']['trainer_id'] != null){
                        $trainer = Trainer::where('id', $item['courses']['trainer_id'])->first();
                        $item['trainer'] = $trainer['first_name'] . ' ' .$trainer['last_name'];
                    }
                    $item['cover_image'] = $item['courses']['cover_image'];
                    $item['created_date'] = $item['courses']['created_at'];
                    unset($item['courses']);
                }

            }
            return response()->json(['success' => true, 'data' => $basketList]);
        }
        return response()->json(['success' => false, 'message' => __('messages.empty-basket')]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(BasketListsRequest $request)
    {
        $items = BasketList::where('course_id', $request->course_id)->get();
        foreach ($items as $item){
            if($item['user_id'] === Auth::id()){
                return response()->json(['fail' => 'This course already in wish list']);
            }
        }
        BasketList::create([
            'user_id' => Auth::id(),
            'course_id' => $request->course_id
        ]);
        return response()->json(['success' => 'Course successfully added on basket list'], 200);
    }

    public function moveToWishList(){

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BasketList  $basketList
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        BasketList::destroy($id);
        return response()->json(['success' => 'Successfully deleted!'], 200);
    }
}
