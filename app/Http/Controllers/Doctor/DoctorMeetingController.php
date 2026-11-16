<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Models\MeetingHistory;
use App\Models\User;
use App\Models\ZoomCredential;
use App\Models\ZoomMeeting;
use App\Services\Zoom;
use App\Traits\GlobalMailTrait;
use App\Traits\RedirectHelperTrait;
use Exception;
use Illuminate\Http\Request;
use Modules\Appointment\app\Models\Appointment;

class DoctorMeetingController extends Controller {
    use GlobalMailTrait, RedirectHelperTrait;

    public function index() {
        $doctor = doctorAuth();
        $meetings = ZoomMeeting::where('doctor_id', $doctor->id)->orderBy('start_time', 'desc')->paginate(10);
        $confirm = __('Are you sure?');
        return view('doctor.zoom.meeting.index', compact('meetings', 'confirm'));
    }
    public function create(Request $request) {
        $doctor = doctorAuth();
        $credential = ZoomCredential::where('doctor_id', $doctor->id)->first();
        if (!$credential) {
            $notification = ['message' => __('Please setup the credentials'), 'alert-type' => 'error'];
            return to_route('doctor.zoom-credential')->with($notification);
        }

        $patients = Appointment::where('doctor_id', $doctor->id)->select('user_id')->groupBy('user_id')->get();
        $patientIds = $patients->pluck('user_id');
        $users = User::whereIn('id', $patientIds)->get();
        return view('doctor.zoom.meeting.create', compact('patients', 'users'));
    }

    public function store(Request $request) {
        $rules = [
            'topic'      => 'required',
            'start_time' => 'required',
            'duration'   => 'required|numeric',
            'patient'    => 'required',
        ];

        $customMessages = [
            'topic.required'      => __('Topic is required'),
            'start_time.required' => __('Start time is required'),
            'duration.required'   => __('Duration is required'),
            'patient.required'    => __('Patient is required'),
        ];

        $this->validate($request, $rules, $customMessages);

        $zoom = new Zoom;
        $dataFormat = $zoom->dataFormat($request->topic, $request->duration, $request->start_time);
        $response = $zoom->createMeeting($dataFormat);

        if ($response['status'] == true) {
            $data = $response['data'];

            $doctor = doctorAuth();
            $start_time = date('Y-m-d H:i:s', strtotime($data['start_time']));
            $topic = $data['topic'];
            $duration = $data['duration'];
            $meeting_id = $data['id'];
            $password = $data['password'];
            $join_url = $data['join_url'];

            $meeting = new ZoomMeeting();
            $meeting->doctor_id = $doctor->id;
            $meeting->start_time = $start_time;
            $meeting->topic = $topic;
            $meeting->duration = $duration;
            $meeting->meeting_id = $meeting_id;
            $meeting->password = $password;
            $meeting->join_url = $join_url;
            $meeting->save();

            if ($request->patient == -1) {
                $appointments = Appointment::where('doctor_id', $doctor->id)->select('user_id')->groupBy('user_id')->get();
                $patientIds = $appointments->pluck('user_id');
                $users = User::whereIn('id', $patientIds)->get();

                foreach ($users as $user) {
                    $history = new MeetingHistory();
                    $history->doctor_id = $doctor->id;
                    $history->user_id = $user->id;
                    $history->meeting_id = $meeting_id;
                    $history->meeting_time = $start_time;
                    $history->duration = $duration;
                    $history->save();

                    // send email
                    try {
                        [$subject, $message] = $this->fetchEmailTemplate('zoom_meeting', ['patient_name' => $user?->name, 'doctor_name' => $doctor?->name, 'meeting_schedule' => $start_time]);
                        $link = [__('Meeting Link') => $join_url];
                        $this->sendMail($user->email, $subject, $message, $link);
                    } catch (Exception $e) {
                        info($e->getMessage());
                    }
                }
            } else {

                $user = User::where('id', $request->patient)->first();

                $history = new MeetingHistory();
                $history->doctor_id = $doctor->id;
                $history->user_id = $user->id;
                $history->meeting_id = $meeting_id;
                $history->meeting_time = $start_time;
                $history->duration = $duration;
                $history->save();

                // send email
                try {
                    [$subject, $message] = $this->fetchEmailTemplate('zoom_meeting', ['patient_name' => $user?->name, 'doctor_name' => $doctor?->name, 'meeting_schedule' => $start_time]);
                    $link = [__('Meeting Link') => $join_url];
                    $this->sendMail($user->email, $subject, $message, $link);
                } catch (Exception $e) {
                    info($e->getMessage());
                }
            }

            return $this->redirectWithMessage(RedirectType::CREATE->value, 'doctor.zoom-meetings');
        }
        $notification = ['message' => __('Invalid Credentials') . ' ' . __('Please setup the credentials'), 'alert-type' => 'error'];
        return to_route('doctor.zoom-credential')->with($notification);

    }

    public function edit($id) {
        $meeting = ZoomMeeting::where('meeting_id', $id)->first();
        if ($meeting) {
            $meeting_user_id = $this->getUserIdOrZero($id);
            $doctor = doctorAuth();
            if ($meeting->doctor_id) {
                $doctor = doctorAuth();
                $patients = Appointment::where('doctor_id', $doctor->id)->select('user_id')->groupBy('user_id')->get();
                $patientIds = $patients->pluck('user_id');
                $users = User::whereIn('id', $patientIds)->get();
                return view('doctor.zoom.meeting.edit', compact('meeting', 'users', 'meeting_user_id'));
            } else {
                $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
                return to_route('doctor.zoom-meetings')->with($notification);
            }
        } else {
            $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
            return to_route('doctor.zoom-meetings')->with($notification);
        }

    }
    public function getUserIdOrZero($meeting_id) {
        $userIds = MeetingHistory::where('meeting_id', $meeting_id)->pluck('user_id');

        return $userIds->count() === 1 ? $userIds->first() : 0;
    }
    public function update($id, Request $request) {
        $rules = [
            'topic'      => 'required',
            'start_time' => 'required',
            'duration'   => 'required|numeric',
            'patient'    => 'required',
        ];

        $customMessages = [
            'topic.required'      => __('Topic is required'),
            'start_time.required' => __('Start time is required'),
            'duration.required'   => __('Duration is required'),
            'patient.required'    => __('Patient is required'),
        ];

        $this->validate($request, $rules, $customMessages);

        $zoom = new Zoom;
        $dataFormat = $zoom->dataFormat($request->topic, $request->duration, $request->start_time);
        $response = $zoom->updateMeeting($id, $dataFormat);
        if ($response['status'] == true) {
            $getMeeting = $zoom->getMeeting($id);
            $data = $getMeeting['data'];

            $doctor = doctorAuth();
            $start_time = date('Y-m-d H:i:s', strtotime($data['start_time']));
            $topic = $data['topic'];
            $duration = $data['duration'];
            $meeting_id = $data['id'];
            $password = $data['password'];
            $join_url = $data['join_url'];

            $meeting = ZoomMeeting::where('meeting_id', $meeting_id)->first();
            $meeting->start_time = $start_time;
            $meeting->topic = $topic;
            $meeting->duration = $duration;
            $meeting->meeting_id = $meeting_id;
            $meeting->password = $password;
            $meeting->join_url = $join_url;
            $meeting->save();

            if ($request->patient == -1) {
                $appointments = Appointment::where('doctor_id', $doctor->id)->select('user_id')->groupBy('user_id')->get();
                $patientIds = $appointments->pluck('user_id');
                $users = User::whereIn('id', $patientIds)->get();

                foreach ($users as $user) {
                    $history = MeetingHistory::where('meeting_id', $meeting_id)->first();
                    $history->user_id = $user->id;
                    $history->meeting_id = $meeting_id;
                    $history->meeting_time = $start_time;
                    $history->duration = $duration;
                    $history->save();

                    // send email
                    try {
                        [$subject, $message] = $this->fetchEmailTemplate('zoom_meeting', ['patient_name' => $user?->name, 'doctor_name' => $doctor?->name, 'meeting_schedule' => $start_time]);
                        $link = [__('Meeting Link') => $join_url];
                        $this->sendMail($user->email, $subject, $message, $link);
                    } catch (Exception $e) {
                        info($e->getMessage());
                    }
                }
            } else {
                $user = User::where('id', $request->patient)->first();

                $history = MeetingHistory::where('meeting_id', $meeting_id)->first();
                $history->user_id = $user->id;
                $history->meeting_id = $meeting_id;
                $history->meeting_time = $start_time;
                $history->duration = $duration;
                $history->save();

                // send email
                try {
                    [$subject, $message] = $this->fetchEmailTemplate('zoom_meeting', ['patient_name' => $user?->name, 'doctor_name' => $doctor?->name, 'meeting_schedule' => $start_time]);
                    $link = [__('Meeting Link') => $join_url];
                    $this->sendMail($user->email, $subject, $message, $link);
                } catch (Exception $e) {
                    info($e->getMessage());
                }
            }

            return $this->redirectWithMessage(RedirectType::CREATE->value, 'doctor.zoom-meetings');

        }
        $notification = ['message' => __('Invalid Credentials') . ' ' . __('Please setup the credentials'), 'alert-type' => 'error'];
        return to_route('doctor.zoom-credential')->with($notification);
    }

    public function destroy($id) {
        $meeting = ZoomMeeting::find($id);
        $meeting_id = $meeting->meeting_id;
        $zoom = new Zoom;
        $zoom->deleteMeeting($meeting_id);
        MeetingHistory::where('meeting_id', $meeting_id)->delete();
        ZoomMeeting::where('meeting_id', $meeting_id)->delete();
        $notification = ['message' => __('Deleted Successfully'), 'alert-type' => 'success'];
        return to_route('doctor.zoom-meetings')->with($notification);
    }

    public function meetingHistory() {
        $doctor = doctorAuth();
        $now = now();
        $histories = MeetingHistory::where('doctor_id', $doctor->id)
            ->whereRaw('DATE_ADD(meeting_time, INTERVAL duration MINUTE) < ?', [$now])
            ->orderBy('meeting_time', 'asc')
            ->paginate(10);
        return view('doctor.zoom.meeting.history', compact('histories'));
    }

    public function upCommingMeeting() {
        $doctor = doctorAuth();
        $now = now();
        $histories = MeetingHistory::where('doctor_id', $doctor->id)
            ->whereRaw('DATE_ADD(meeting_time, INTERVAL duration MINUTE) > ?', [$now])
            ->orderBy('meeting_time', 'asc')
            ->paginate(10);
        return view('doctor.zoom.meeting.upcoming', compact('histories'));
    }
}
