<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Appointment\app\Models\Appointment;
use Modules\Order\app\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class ProfileController extends Controller {
    public function dashboard() {
        $user = userAuth();
        $appointments = Appointment::where('user_id', $user->id)->get();
        $orders = Order::where('user_id', $user->id)->get();
        return view('patient.profile.dashboard', compact('user', 'appointments', 'orders'));
    }
    public function updateProfile(Request $request) {

        $user = userAuth();
        $rules = [
            'name'       => 'required',
            'email'      => 'required|unique:users,email,' . $user->id,
            'phone'      => 'required',
            'age'        => 'required',
            'occupation' => 'required',
            'gender'     => 'required',
            'address'    => 'required',
            'country'    => 'required',
            'city'       => 'required',
        ];
        $customMessages = [
            'name.required'       => __('Name is required'),
            'email.required'      => __('Email is required'),
            'email.unique'        => __('Email already exist'),
            'phone.required'      => __('Phone is required'),
            'address.required'    => __('Address is required'),
            'age.required'        => __('age is required'),
            'occupation.required' => __('Occupation is required'),
            'gender.required'     => __('Gender is required'),
            'country.required'    => __('Country is required'),
            'city.required'       => __('City is required'),
        ];
        $this->validate($request, $rules, $customMessages);

        try {
            DB::beginTransaction();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->ready_for_appointment = 1;

            if ($user && $request->hasFile('image')) {
                $file_name = uploadAndOptimizeImage(
                    file: $request->image,
                    oldFile: $user->image,
                );
                $user->image = $file_name;
            }
            $user->save();

            UserDetails::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone'                   => $request->phone,
                    'guardian_name'           => $request->guardian_name,
                    'guardian_phone'          => $request->guardian_phone,
                    'age'                     => $request->age,
                    'occupation'              => $request->occupation,
                    'gender'                  => $request->gender,
                    'address'                 => $request->address,
                    'country'                 => $request->country,
                    'city'                    => $request->city,
                    'date_of_birth'           => $request->date_of_birth,
                    'weight'                  => $request->weight,
                    'height'                  => $request->height,
                    'health_insurance_number' => $request->health_insurance_number,
                    'health_card_number'      => $request->health_card_number,
                    'health_card_provider'    => $request->health_card_provider,
                    'blood_group'             => $request->blood_group,
                    'disabilities'            => $request->disabilities,
                ]);
            DB::commit();

            $notification = __('Your profile Updated successfully');
            $notification = ['message' => $notification, 'alert-type' => 'success'];

            return redirect()->back()->with($notification);
        } catch (\Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            $notification = __('Your profile update failed');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }

    }
    public function changePassword() {
        $user = userAuth();
        return view('patient.profile.change-password', compact('user'));
    }
    public function storePassword(Request $request) {
        $rules = [
            'current_password' => 'required',
            'password'         => 'required|min:4|confirmed',
        ];
        $customMessages = [
            'current_password.required' => __('Current password is required'),
            'password.required'         => __('Password is required'),
            'password.min'              => __('Password minimum 4 character'),
            'password.confirmed'        => __('Confirm password does not match'),
        ];
        $this->validate($request, $rules, $customMessages);

        $user = userAuth();
        if (Hash::check($request->current_password, $user->password)) {
            $user->password = Hash::make($request->password);
            $user->save();

            $notification = __('Password change successfully');
            $notification = ['message' => $notification, 'alert-type' => 'success'];

            return redirect()->back()->with($notification);

        } else {
            $notification = __('Current password does not match');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }
    }

    public function appointments() {
        $user = userAuth();
        $appointments = Appointment::where('user_id', $user->id)->orderBy('id', 'desc')->paginate(10);
        return view('patient.profile.appointment', compact('user', 'appointments'));
    }

    public function showAppointment($id) {
        $appointment = Appointment::find($id);
        $user = userAuth();
        return view('patient.profile.show-appointment', compact('user', 'appointment'));
    }

    public function orders() {
        $user = userAuth();
        $orders = Order::where('user_id', $user->id)->orderBy('id', 'desc')->paginate(10);
        return view('patient.profile.order', compact('user', 'orders'));
    }
    function printPrescription($id) {
        $appointment = userAuth()->appointments()->treated()->find($id);

        if ($appointment) {
            $data = [
                'appointment'         => $appointment,
            ];
            $pdf = Pdf::loadView( 'prescription', $data )->setPaper( 'a4', 'landscape' );
            return $pdf->stream('prescription_'.$appointment->id. '.pdf' );
        } else {
            $notification = __('No appointment found.');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }
    }
}
