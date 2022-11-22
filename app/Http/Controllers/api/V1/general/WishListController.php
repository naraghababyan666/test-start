<?php

namespace App\Http\Controllers\api\V1\general;

use App\Http\Controllers\Controller;
use App\Http\Requests\WishListsRequest;
use App\Models\BasketList;
use App\Models\WishList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $wishList = WishList::where('user_id', Auth::id())->get();
        return response()->json(['list' => $wishList]);
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
    public function store(WishListsRequest $request)
    {
        $items = WishList::where('course_id', $request->course_id)->get();
        foreach ($items as $item){
            if($item['user_id'] === Auth::id()){
                return response()->json(['fail' => 'This course already in wish list']);
            }
        }
        WishList::create([
            'user_id' => Auth::id(),
            'course_id' => $request->course_id
        ]);
        return response()->json(['success' => 'Course successfully added on wish list'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WishList  $wishList
     * @return \Illuminate\Http\Response
     */
    public function show(WishList $wishList)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WishList  $wishList
     * @return \Illuminate\Http\Response
     */
    public function edit(WishList $wishList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WishList  $wishList
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WishList $wishList)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\JSonResponse
     */
    public function destroy($id)
    {
       WishList::destroy($id);
       return response()->json(['success' => 'Successfully deleted!'], 200);
    }
}
