<?php

namespace App\Http\Controllers\api\V1\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Language;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use PHPUnit\Exception;
use DB;

class CategoryController extends Controller
{

    public function getCategories(Request $request)
    {
        $language = $request["language_code"]?  Language::getLanguage($request["language_code"]): Language::getLanguage();
        Category::$language = $language;
        $categories = Category::with([
            "children"
        ])->whereNull(["parent_id"])->whereHas('translation')->get();
        return response(new UserResource($categories))
            ->setStatusCode(200)->header('Status-Code', '200');
    }

    public function getCategory($id, Request $request)
    {
        $language = $request["language_code"]?  Language::getLanguage($request["language_code"]): Language::getLanguage();
        Category::$language = $language;
        $category = Category::with(["translation","children"])->find($id);
        return response(new UserResource($category))
            ->setStatusCode(200)->header('Status-Code', '200');
    }

    public function createCategories(CategoryRequest $request)
    {
                try {
                    if($request['categories']){
                        foreach ($request["categories"] as $category) {
                            $model = new Category();
                            $categoryModel = $this->saveCategory($model, $category);
                            if (!empty($categoryModel->id)) {
                                foreach ($category["category_info"] as $info) {
                                    $categoryTranslation = new  CategoryTranslation();
                                    $categoryTranslation->title = $info["title"];
                                    $categoryTranslation->language_id = Language::getLanguage($info["language_code"] );
                                    $categoryTranslation->category_id = $categoryModel->id;
                                    $categoryTranslation->save();
                                }
                            }
                        }
                        return response(__("messages.category_created"))
                            ->setStatusCode(200)->header('Status-Code', '200');
                    }else{
                        return response()->json(['success' => false, 'message' => 'Categories field is required!']);
                    }

                } catch (Exception $e) {
                    throw new HttpResponseException(response()->json([
                        'message' => $e->getMessage(),
                    ], $e->getCode())->header('Status-Code', $e->getCode()));

                }
    }


    public function updateCategories(CategoryRequest $request)
    {
                try {
                    foreach ($request["categories"] as $category) {
                        if (isset($category["id"])) {
                            $model = Category::query()->find($category["id"]);
                            $categoryModel = $this->saveCategory($model, $category);
                            if (!empty($categoryModel->id)) {
                                foreach ($category["category_info"] as $info) {
                                    $categoryTranslation = CategoryTranslation::query()->where("category_id", $category["id"])->where("language_id",Language::getLanguage($info["language_code"]) )->first();
                                    if (!empty($categoryTranslation)) {
                                        $categoryTranslation->title = $info["title"];
                                        $categoryTranslation->save();
                                    }
                                }
                            } else {
                                throw new HttpResponseException(response()->json([
                                    'success' => false,
                                    'message' => 'Failed to save category.',
                                ], 500)->header('Status-Code', '500'));
                            }
                        } else {
                            throw new HttpResponseException(response()->json([
                                'success' => false,
                                'message' => 'Category not found.',
                            ], 404)->header('Status-Code', '404'));
                        }
                    }
                    return response(__("messages.category_updated"))
                        ->setStatusCode(200)->header('Status-Code', '200');
                } catch (Exception $e) {
                    throw new HttpResponseException(response()->json([
                        'message' => $e->getMessage(),
                    ], 401)->header('Status-Code', 401));

                }
    }


    public function deleteCategories(Request $request)
    {

                $nfIds = [];
                if (!empty($request["ids"])) {
                    foreach ($request["ids"] as $categoryID) {
                        $category = Category::query()->find($categoryID);
                        if ($category) {
                            $category->delete();
                        } else {
                            $nfIds [] = $categoryID;
                        }
                    }
                }

                if (!empty($nfIds)) {
                    $ids = implode(",", $nfIds);
                    $message = 'Category by ' . $ids . ' id  not found ';
                    if (count($nfIds) > 1) {
                        $message = 'Categories by ' . $ids . ' ids  not found ';

                    }
                    throw new HttpResponseException(response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 200)->header('Status-Code', 200));
                } else {
                    $data = [
                        'success' => true,
                        "message" => __("messages.delete_category")
                    ];
                    return response($data)
                        ->setStatusCode(200)->header('Status-Code', '200');
                }

    }

    private function saveCategory($categoryModel, $category)
    {
        try {
            $categoryModel->ordering = $category["ordering"];
            if ($category["parent_id"] > 0) {
                if (Category::isExists($category["parent_id"])) {
                    $categoryModel->parent_id = $category["parent_id"];
                } else {
                    throw new HttpResponseException(response()->json([
                        'success' => false,
                        'message' => 'Parent category not found.',
                    ], 404)->header('Status-Code', '404'));
                }
            } elseif ($category["parent_id"] == 0) {
                $categoryModel->parent_id = null;
            }

            if (!empty($category["icon"])) {
                if ($file = $category["icon"]) {
                    $this->validate($category, ['icon' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',]);
                    $filename= 'images/icons/'.date('YmdHi').'.'.$file->extension();
                    $file-> move(public_path('images/icons'), $filename);
                    $categoryModel->icon = $filename;
                }
            }
            $categoryModel->save();
            return $categoryModel;
        } catch (Exception $e) {
            throw new HttpResponseException(response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode())->header('Status-Code', $e->getCode()));

        }
    }

    public function getCategoriesForFilter(Request $request)
    {
        $language = $request["language_code"]?  Language::getLanguage($request["language_code"]): Language::getLanguage();
        Category::$language = $language;

        $sql = "SELECT c.id, c_tr.title,c.parent_id from categories as c left join category_translations as c_tr on c_tr.category_id=c.id where c_tr.language_id={$language} order by c.ordering,c.parent_id ASC";
        $data = DB::select($sql);
        $result = [];
        foreach($data as $key=>$val){

          if(!$val->parent_id){
              $result[$val->id] = ['id'=>$val->id,'text'=>$val->title];
          }else{
              if(!isset( $result[$val->parent_id]['children']) ){
                  $result[$val->parent_id]['children'] = [];
              }
              $result[$val->parent_id]['children'][] =  ['id'=>$val->id,'text'=>$val->title,'children'=>[]];
          }
        }

        foreach($result as $k=>$v ){
            if(empty($v['children'])){
                unset($v['children']);
            }
         $new_result[] =  $v;
        }

        return response()->json([
            'success' => true,
            'data' => $new_result,
        ])->header('Status-Code', '200');



    }
}
