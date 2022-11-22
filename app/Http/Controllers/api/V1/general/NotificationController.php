<?php

namespace App\Http\Controllers\api\V1\general;

use App\Http\Controllers\Controller;
use App\Mail\UpstartMail;
use App\Models\Notification;
use App\Notifications\UpstartNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;


class NotificationController extends Controller
{
    public function getNotifications()
    {
        if (Auth::check()) {
            $notifications = Notification::query()->where("user_id", auth()->id())->get();
            return response()->json([
                'success' => true,
                'data' => $notifications,
            ])->header('Status-Code', '200');

        } else {
            return response()->json([
                'success' => false,
                'message' => __("messages.forbidden"),
            ], 403)->header('Status-Code', '403');
        }
    }

    public function getNotification($id)
    {
            $notifications = Notification::query()->where("user_id", auth()->id())->where("id", $id)->get();
            return response()->json([
                'success' => true,
                'data' => $notifications,
            ])->header('Status-Code', '200');

    }
    public function getNewNotifications()
    {
        $notifications = Notification::query()->where("user_id", auth()->id())->where("status", Notification::NEW_NOTIFICATION)->get();
            return response()->json([
                'success' => true,
                'data' => $notifications,
            ])->header('Status-Code', '200');
    }

    public function removeNotification($id){
        $notification = Notification::query()->where('id', '=', $id)->where('user_id', '=', Auth::id())->first();
        if(is_null($notification)){
            return response()->json(['success' => false, 'message' => __('messages.notification-not-found')], 402);
        }
        $notification->delete();
        return response()->json(['success' => true, 'message' => __('messages.notification-remove')]);
    }

    public function markAsRead(){
        $notification = Notification::query()->where('user_id', Auth::id())->get();
        if(count($notification)!== 0){
            Notification::query()->where('user_id', Auth::id())->update(['status' => 1]);
            return response()->json(['success' => true, 'message' => 'Notifications marked as read.']);
        }
        return response()->json(['success' => false, 'message' => __('messages.no-notifications')]);
    }

    public static function store($data)
    {
        $notification =  Notification::create($data);
        if($data["type"]=="email"){
            Mail::to($data['email'])->send(new UpstartMail($data));
        }
        return $notification;
    }

    public static function changeNotificationStatus(Request $request){
        $notification = Notification::where('id', $request['id'])->where('user_id', Auth::id())->first();
        if($notification){
            if($notification->status == 0){
                $notification->status = 1;
                $notification->save();
                return response()->json(['success'=> true, 'message' => 'Notification status changed!']);
            }else{
                return response()->json(['success'=> true, 'message' => 'Notification status changed!']);
            }
        }else{
            return response()->json(['success'=> false, 'message' => 'Notification not found!']);
        }
    }
}
