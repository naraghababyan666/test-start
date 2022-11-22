<?php

namespace App\Http\Controllers\api\V1\trainer;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\TrainerMetaRequest;
use App\Models\TrainerMeta;
use \App\Models\User;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\isNull;

class TrainerMetaController extends Controller
{
    public function saveTrainerMeta(TrainerMetaRequest $request){
        $data = $request->validated();
        $trainerData = TrainerMeta::query()->where('user_id', Auth::id())->with('user')->first();
        if(isset($request->all()['links'])){
            foreach ( $request->all()['links'] as $item => $value){
                if(!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)){
                    return response()->json(['success' => false, 'message' => "Invalid ${item} url"]);
                }
            }
        }
        if(is_null($trainerData)){
            $newData = TrainerMeta::create([
                'user_id' => Auth::id(),
                'headline' => $data['headline']??null,
                'bio' => $data['bio']??null,
                'links' => json_encode($request->all()['links'])??null
            ]);
            $userData =  User::where('id', $newData['user_id'])->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'avatar' => $data['avatar']??null

            ]);
            $user = User::query()->where('id', $newData['user_id'])->select('id', 'first_name', 'last_name', 'email', 'avatar')->first();
            $info = TrainerMeta::query()->where('id', '=', $newData->id)->first();
            return $this->extracted($user, $info);
        }else{
            $trainerData->update([
                'headline' => $data['headline']??$trainerData->headline,
                'bio' => $data['bio']??$trainerData->bio,
            ]);
            if(isset($request->all()['links'])){
                $trainerData->update(['links' => json_encode($request->all()['links'])??$trainerData->links]);
            }
            $trainerData->save();
            $userData =  User::where('id', Auth::id())->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'avatar' => $data["avatar"]??null
            ]);

            $user = User::query()->where('id', Auth::id())->select('id', 'first_name', 'last_name', 'email', 'avatar')->first();
            $info = TrainerMeta::query()->where('id', '=', $trainerData->id)->first();
            return $this->extracted($user, $info);
        }
    }

    public function getTrainerMeta($id){
        $data = TrainerMeta::query()->where('user_id', $id)->first();
        $user = User::query()->where('id', '=', $id)->select('email', 'first_name', 'last_name', 'avatar')->first();
        if(is_null($data)){
            if(is_null($user)){
                return response()->json(['success' => false, 'message' => __('messages.user_not_found')]);
            }
            return response()->json(['success' => true, 'data' => $user]);
        }

        $data['links'] = json_decode($data['links'],true);
        $data['email'] = $user['email'];
        $data['first_name'] = $user['first_name'];
        $data['last_name'] = $user['last_name'];
        $data['avatar'] = $user['avatar']?env("APP_URL")."/".$user['avatar']:null;
        return response()->json(['success' => true,'data' => $data]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|object|\Illuminate\Database\Eloquent\Builder|null $user
     * @param \Illuminate\Database\Eloquent\Model|object|\Illuminate\Database\Eloquent\Builder|null $info
     * @return \Illuminate\Http\JsonResponse
     */
    public function extracted(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|null $user, \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|null $info): \Illuminate\Http\JsonResponse
    {
        $info['user_id'] = $user['id'];
        $info['first_name'] = $user['first_name'];
        $info['last_name'] = $user['last_name'];
        $info['email'] = $user['email'];
        $info['avatar'] = $user['avatar']?env("APP_URL")."/".$user['avatar']:null;

        return response()->json(['success' => true, 'data' => $info]);
    }
}
