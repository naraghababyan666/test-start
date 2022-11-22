<?php

namespace App\Http\Controllers\api\V1\general;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CourseResource;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Course;
use App\Models\Language;
use App\Models\Role;
use App\Models\Trainer;
use Dotenv\Validator;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;

class SiteController extends Controller
{
    public  $filter = [];

     public function getLanguages(){
         $languages = Language::query()->get();
         return  response($languages)->setStatusCode(200)->header('Status-Code', '200');
     }
     public function getRoles(){
         $roles = Role::query()->get();
         return  response($roles)->setStatusCode(200)->header('Status-Code', '200');
     }
     public function getCourseStatuses(){
         $data = Course::getStatus(0, true);
         return  response($data)->setStatusCode(200)->header('Status-Code', '200');
     }
     public function getCourseTypes(){
         $data = Course::getType(0, true);
         return  response($data)->setStatusCode(200)->header('Status-Code', '200');
     }
     public function getCourseLevels(){
         $data = Course::getLevels(0,  true);
         return  response($data)->setStatusCode(200)->header('Status-Code', '200');
     }


     public function autocompleteText($text){
     $queryCourse = DB::table('courses');
     $coursesListForFilterText = $queryCourse->whereRaw("LOWER(`title`) LIKE ? ",['%'.trim(strtolower($text)).'%'])
             ->orWhereRaw("LOWER(`sub_title`) LIKE ? ",['%'.trim(strtolower($text)).'%'])->get();


         $queryCategories = DB::table('category_translations');
     $categories = $queryCategories->whereRaw("LOWER(`title`) LIKE ? ",['%'.trim(strtolower($text)).'%'])->get();

     $courseListForReturn = $queryCourse
             ->whereRaw("LOWER(`title`) LIKE ? ",['%'.trim(strtolower($text)).'%'])
             ->where('status', '=', 3)
             ->inRandomOrder()->limit(4)->select('id', 'cover_image', 'title', 'trainer_id')->get();

         foreach ($courseListForReturn as $item){
             $trainer = Trainer::where('id', $item->trainer_id)->first();
             $item->trainer_name = $trainer['first_name'] .' '. $trainer['last_name'];
         }
         if($coursesListForFilterText || $categories){
             $this->loop($coursesListForFilterText, 'title', $text);
             $this->loop($coursesListForFilterText, 'sub_title', $text);
             $this->loop($categories, 'title', $text);
             $this->filter = array_unique($this->filter);
             if(count($courseListForReturn) + count($this->filter) >= 10){
                 if(count($this->filter) > 10){
                     $this->filter = array_slice($this->filter, 0, 10);
                 }
             }
             $texts = [];
             foreach ($this->filter as $text){
                 $texts[] = $text;
             }
             return response()->json([
                 'success' => true,
                 'text' => $texts,
                 'courses' => $courseListForReturn
             ], 200);

         }
         return response()->json(['fail' => 'No filtered text'], 204);

     }

     function loop($object, $column, $text){
//         dd($object[1]->$column, $text, str_contains(strtolower($object[1]->$column), $text));
         foreach ($object as $item) {
             if (str_contains(strtolower($item->$column), $text)) {
                 array_push($this->filter, $item->$column);
             }
         }
     }

    public function searchFilter(Request $request)
    {
        $data = $request->all();
        $language = $data["language_code"] ?  Language::getLanguage($data["language_code"]): Language::getLanguage();
        $where_text = $find_lists =  '';
        $find = [];
        if(isset($data['user_id'])){
            $find[] = " (select count(*) from wish_lists as w where w.user_id={$data['user_id']} and c.id=w.course_id) as in_wishlist ";
            $find[] = " (select count(*) from basket_lists as b where b.user_id={$data['user_id']} and c.id=b.course_id) as in_basket ";
            $find_lists = implode(',',$find).',';
        }
        $where[] = 'c.status = ?';
        $limit = (isset($data['limit']))?$data['limit']:10;
        $page =  (isset($data['page']))?$data['page']:1;
        $skip =  ($page-1)*$limit;

        if(isset($data['categories']) && !empty($data['categories'])){
            $where[] = "cat.id IN (".$data['categories'].")";
        }
        if(isset($data['type']) && !empty($data['type'])){
            $where[] = "c.type IN (".$data['type'].")";
        }
        if(isset($data['type']) && !empty($data['type'])){
            $where[] = "c.type IN (".$data['type'].")";
        }
        if(isset($data['level']) && !empty($data['level'])){
            $data['level'] = $data['level'].', '.Course::ALL_LEVELS;
            $where[] = "c.level IN (".$data['level'].")";
        }
        if(isset($data['language_id']) && !empty($data['language_ids'])){
            $where[] = "c.language IN (".$data['language_ids'].")";
        }
        if(isset($data['search_text']) && !empty($data['search_text'])){
            $where[] = "(c.title LIKE '%".$data['search_text']."%' OR c.sub_title LIKE '%".$data['search_text']."%') ";
        }
        if(isset($data['currency']) && !empty($data['currency'])){
            $where[] = "(c.currency = '{$data['currency']}' AND c.price BETWEEN {$data['price_from']} AND {$data['price_to']})";
        }
        if(!empty($where)){
            $where_text = implode(' AND ', $where);
        }

        $sql = "SELECT {$find_lists} c.type, c.cover_image, c.id, c.sub_title, c.title, cat.title as category_title, c.currency, c.price,
                IF((c.trainer_id > 0), CONCAT(t.first_name,' ',t.last_name), CONCAT(u.first_name,' ',u.last_name)) AS trainer_name,
                IF((c.trainer_id > 0), t.avatar, u.avatar) AS trainer_avatar,
                (select avg(rate) from reviews as r where r.course_id=c.id) as rating
                from courses as c 
                LEFT join users as u on c.user_id=u.id
                LEFT join category_translations as cat on (c.category_id=cat.id AND cat.language_id = ?)
                LEFT join trainers as t on c.trainer_id=t.id
                where {$where_text}
                  ";

        $sql_count = "select count(id) as total from ($sql) as page_count";

        $sql_updated = $sql.' '." LIMIT {$skip}, $limit";

        $result_count = DB::select($sql_count,[$language,Course::APPROVED]);

        $result = DB::select($sql_updated,[$language,Course::APPROVED]);
        $resultArray = json_decode(json_encode($result), true);
        foreach($resultArray as $key => $val){
            if(!isset($val['in_wishlist'])){
                $resultArray[$key]['in_wishlist'] = 0;
                $resultArray[$key]['in_basket'] = 0;
            }
        }
        return response()->json(['data' => $result, 'total_count'=>$result_count[0]->total], 200);
    }
     public function fileUpload(Request $request){

         $path = FileHelper::fileUpload($request);
         $url = env("APP_URL")."/".$path;
         return response()->json([
             'success' => true,
             "data" => [
                 'path' => $path,
                 'url' => $url
             ]
         ], 200);
     }
}
