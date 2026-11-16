<?php

namespace App\Http\Controllers\API\Doctor;

use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Events\PatientChatMessage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\GlobalSetting\app\Models\Setting;

class MessageController extends Controller
{
    public function index(): JsonResponse {
        $doctorId = auth()->guard('doctor_api')->user()->id;

        $users = User::withCount(['messages as unread_messages_count' => function ($query) use ($doctorId) {
            $query->where('doctor_view', 0)->where('doctor_id', $doctorId);
        }])
        ->whereHas('appointments', function ($query) use ($doctorId) {
            $query->where('doctor_id', $doctorId)->paymentSuccess();
        })->get()->map(function ($user) {
            $user->latest_message = $user->messages()->select('message','send_doctor','send_user')->latest()->first();
            return $user;
        });

        if ($users->count()) {
            return response()->json(['status' => 'success', 'appointed_patients' => $users], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }


    public function getMessage($patient_id): JsonResponse {
        $my_id = auth()->guard('doctor_api')->user()?->id;
        Message::where(['doctor_id' => $my_id, 'user_id' => $patient_id])->update(['doctor_view' => 1]);
        $messages = Message::where(['doctor_id' => $my_id, 'user_id' => $patient_id])->get();

        if ($messages->count()) {
            return response()->json(['status' => 'success', 'messages' => $messages], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function sendMessage(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required',
            'message'     => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }
        $my_id = auth()->guard('doctor_api')->user()?->id;

        // Save message to the database
        $message = new Message();
        $message->doctor_id = $my_id;
        $message->user_id = $request->receiver_id;
        $message->message = $request->message;
        $message->send_doctor = true;
        $message->save();

        // Broadcast the event
        $data = (object) [
            'message' => $message->message,
            'sender_id' => $my_id,
            'receiver_id' => $message->user_id,
            'created_at' => formattedDateTime($message->created_at),
            'un_seen'     => Message::where([
                'user_id' => $message->user_id,
                'doctor_id' => $message->doctor_id,
                'user_view' => 0,
            ])->count(),
        ];
        $pusher_status = Setting::where('key', 'pusher_status')->value('value');
        if($pusher_status == 'active'){
            event(new PatientChatMessage($data));
        }

        return response()->json(['status' => 'success','message'=> Message::where('id',$message?->id)->first()], 200);
    }
    public function seenMessage($patient_id): JsonResponse {
        $my_id = auth()->guard('doctor_api')->user()?->id;
        Message::where(['doctor_id' => $my_id, 'user_id' => $patient_id])->update(['doctor_view' => 1]);
        return response()->json(['status' => 'success','message'=>'All messages from this patient are seen'], 200);
    }
}
