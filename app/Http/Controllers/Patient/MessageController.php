<?php

namespace App\Http\Controllers\Patient;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Events\DoctorChatMessage;
use App\Http\Controllers\Controller;
use Modules\Doctor\app\Models\Doctor;
use Modules\GlobalSetting\app\Models\Setting;
use Modules\Appointment\app\Models\Appointment;

class MessageController extends Controller {
    public function index() {
        $user = userAuth();
        $doctors = Appointment::with('doctor')->where('user_id', $user->id)->groupBy('doctor_id')->select('doctor_id')->get();
        return view('patient.profile.message.index', compact('user', 'doctors'));
    }

    public function messageBox($slug) {
        $doctor = Doctor::where('slug', $slug)->first();
        $doctor_id = $doctor->id;
        $user = userAuth();
        Message::where(['user_id' => $user->id, 'doctor_id' => $doctor_id])->update(['user_view' => 1]);
        $messages = Message::where(['user_id' => $user->id, 'doctor_id' => $doctor_id])->get();

        $doctors = Appointment::with('doctor')->where('user_id', $user->id)->groupBy('doctor_id')->select('doctor_id')->get();
        return view('patient.profile.message.single-message', compact('messages', 'doctors', 'user', 'doctor_id'));
    }

    public function getMessage($doctor_id) {
        $user = userAuth();
        $my_id = $user->id;
        Message::where(['user_id' => $user->id, 'doctor_id' => $doctor_id])->update(['user_view' => 1]);
        $messages = Message::where(['user_id' => $user->id, 'doctor_id' => $doctor_id])->get();

        $view = view('patient.profile.message.message-box', compact('messages'))->render();
        $unseen_message = Message::where(['user_id' => $user->id, 'user_view' => 0])->whereNot('doctor_id', $doctor_id)->count();

        // Return a JSON response
        return response()->json([
            'view'           => $view,
            'unseen_message' => $unseen_message,
        ]);
    }
    public function seenMessage($doctor_id) {
        $my_id = userAuth()?->id;
        Message::where(['user_id' => $my_id, 'doctor_id' => $doctor_id])->update(['user_view' => 1]);
        return response()->json(['status' => 'success'], 200);
    }

    public function sendMessage(Request $request) {
        $this->validate($request, [
            'receiver_id' => 'required',
            'message'     => 'required',
        ]);

        $user = userAuth();

        // Save message to the database
        $message = new Message();
        $message->doctor_id = $request->receiver_id;
        $message->user_id = $user->id;
        $message->message = strip_tags($request->message);
        $message->send_user = true;
        $message->save();

        // Broadcast the event
        $data = (object) [
            'message'     => $message->message,
            'sender_id' => $user->id,
            'receiver_id' => $message->doctor_id,
            'created_at'  => formattedDateTime($message->created_at),
            'un_seen'     => Message::where([
                'user_id' => $message->user_id,
                'doctor_id' => $message->doctor_id,
                'doctor_view' => 0,
            ])->count(),
        ];
        $pusher_status = cache('setting')?->pusher_status ?? Setting::where('key', 'pusher_status')->value('value');
        if($pusher_status == 'active'){
            event(new DoctorChatMessage($data));
        }

        return response()->json(['doctor_id' => $request->receiver_id]);
    }
}
