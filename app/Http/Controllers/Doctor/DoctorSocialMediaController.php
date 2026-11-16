<?php

namespace App\Http\Controllers\Doctor;

use Closure;
use App\Enums\RedirectType;
use Illuminate\Http\Request;
use App\Models\DoctorSocialMedia;
use App\Traits\RedirectHelperTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Http\RedirectResponse;

class DoctorSocialMediaController extends Controller {
    use RedirectHelperTrait;
    /**
     * Display a listing of the resource.
     */
    public function index() {
        $setting = cache()->get('setting');
        if($setting->doctor_can_add_social_links == 'inactive'){
            abort(404);
        }
        $social_links = doctorAuth()->socialMedia()->oldest()->take($setting?->doctor_social_links_limit)->get();
        return view('doctor.social_media.index', compact('social_links'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse {
        $setting = cache()->get('setting');
        if(doctorAuth()->socialMedia()->count() >= $setting?->doctor_social_links_limit){
            $notification = __('You have reached the limit. You can only add social links').' ' . $setting?->doctor_social_links_limit;
            $notification = ['message' => $notification, 'alert-type' => 'error'];
            return back()->with($notification);

        }
        $request->validate([
            'icon' => 'required|image|max:512|mimes:jpeg,jpg,png,gif,webp,svg',
            'link' => 'required',
        ], [
            'icon.required' => __('The icon is required.'),
            'icon.image'   => __('Icon is must be an image.'),
            'icon.max'   => __('The icon may not be greater than 512 kilobytes in size.'),
            'link.required' => 'Link is required.',
        ]);
        if ($request->hasFile('icon')) {
            $item = new DoctorSocialMedia();
            $item->doctor_id = doctorAuth()->id;
            $item->link = $request->link;
            $file_name = uploadAndOptimizeImage(
                file: $request->icon,
            );
            $item->icon = $file_name;

            $item->save();

            return $this->redirectWithMessage(RedirectType::CREATE->value);
        } else {
            $notification = __('The icon is required.');
            $notification = ['message' => $notification, 'alert-type' => 'error'];
            return back()->with($notification);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse {
        $request->validate([
            'icon' => 'sometimes|image|max:512|mimes:jpeg,jpg,png,gif,webp,svg',
            'link' => 'required',
        ], [
            'icon.required' => __('The icon is required.'),
            'icon.image'   => __('Icon is must be an image.'),
            'icon.max'   => __('The icon may not be greater than 512 kilobytes in size.'),
            'link.required' => 'Link is required.',
        ]);

        $item = doctorAuth()->socialMedia()->findOrFail($id);
        $item->link = $request->link;

        if ($item && $request->hasFile('icon')) {
            $file_name = uploadAndOptimizeImage(
                file: $request->icon,
                oldFile: $item->icon
            );
            $item->icon = $file_name;
        }

        $item->save();



        return $this->redirectWithMessage(RedirectType::UPDATE->value);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        $item = doctorAuth()->socialMedia()->findOrFail($id);
        if ($item->icon) {
            if (File::exists(public_path($item->icon))) {
                unlink(public_path($item->icon));
            }
        }
        $item->delete();
        return $this->redirectWithMessage(RedirectType::DELETE->value);
    }

    public function statusUpdate($id) {
        $item = doctorAuth()->socialMedia()->find($id);
        $status = $item->status == 1 ? 0 : 1;
        $item->update(['status' => $status]);

        $notification = __('Updated successfully');
        return response()->json([
            'success' => true,
            'message' => $notification,
        ]);
    }
}
