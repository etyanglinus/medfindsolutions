<?php

namespace App\Http\Controllers\API\Patient;

use App\Events\DoctorChatMessage;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Doctor\app\Models\Doctor;
use Modules\GlobalSetting\app\Models\Setting;

class MessageController extends Controller {
    public function index(): JsonResponse {
        $userId = auth()->guard('api')->user()->id;

        $doctors = Doctor::withCount(['messages as unread_messages_count' => function ($query) use ($userId) {
            $query->where('user_view', 0)->where('user_id', $userId);
        }])
        ->whereHas('appointments', function ($query) use ($userId) {
            $query->where('user_id', $userId)->paymentSuccess();
        })->get()->map(function ($doctor) {
            $doctor->latest_message = $doctor->messages()->select('message','send_doctor','send_user')->latest()->first();
            return $doctor;
        });

        if ($doctors->count()) {
            return response()->json(['status' => 'success', 'appointed_doctors' => $doctors], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function getMessage($doctor_id): JsonResponse {
        $user = auth()->guard('api')->user();
        Message::where(['user_id' => $user->id, 'doctor_id' => $doctor_id])->update(['user_view' => 1]);
        $messages = Message::where(['user_id' => $user->id, 'doctor_id' => $doctor_id])->get();

        $unseen_message = Message::where(['user_id' => $user->id, 'user_view' => 0])->whereNot('doctor_id', $doctor_id)->count();

        if ($messages->count()) {
            return response()->json(['status' => 'success', 'messages' => $messages, 'unseen_message' => $unseen_message], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function seenMessage($doctor_id): JsonResponse {
        $my_id = auth()->guard('api')->user()?->id;
        Message::where(['user_id' => $my_id, 'doctor_id' => $doctor_id])->update(['user_view' => 1]);
        return response()->json(['status' => 'success', 'message' => 'All messages from this doctor are seen'], 200);
    }

    public function sendMessage(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required',
            'message'     => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }

        $user = auth()->guard('api')->user();

        // Save message to the database
        $message = new Message();
        $message->doctor_id = $request->receiver_id;
        $message->user_id = $user->id;
        $message->message = $request->message;
        $message->send_user = true;
        $message->save();

        // Broadcast the event
        $data = (object) [
            'message'     => $message->message,
            'sender_id' => $user->id,
            'receiver_id' => $message->doctor_id,
            'created_at'  => formattedDateTime($message->created_at),
            'un_seen'     => Message::where([
                'user_id'     => $message->user_id,
                'doctor_id'   => $message->doctor_id,
                'doctor_view' => 0,
            ])->count(),
        ];
        $pusher_status = Setting::where('key', 'pusher_status')->value('value');
        if ($pusher_status == 'active') {
            event(new DoctorChatMessage($data));
        }

        return response()->json(['status' => 'success', 'message' => Message::where('id', $message?->id)->first()], 200);
    }
}
