<?php

namespace App\Http\Controllers\API\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorSocialMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class DoctorSocialMediaController extends Controller {
    public function index() {
        $setting = cache()->get('setting');
        $doctor = auth()->guard('doctor_api')->user();
        $social_links = $doctor->socialMedia()->select('id', 'icon', 'link')->active()->oldest()->take($setting?->doctor_social_links_limit)->get();
        if ($social_links) {
            return response()->json(['status' => 'success', 'data' => $social_links], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse {
        $doctor = auth()->guard('doctor_api')->user();
        $setting = cache()->get('setting');
        if ($doctor->socialMedia()->count() >= $setting?->doctor_social_links_limit) {
            return response()->json(['status' => 'failed', 'message' => [
                'limit_reached' => __('You have reached the limit. You can only add social links') . ' ' . $setting?->doctor_social_links_limit,
            ]], 422);

        }

        $validator = Validator::make($request->all(), [
            'icon' => 'required|image|max:512|mimes:jpeg,jpg,png,gif,webp,svg',
            'link' => 'required',
        ], [
            'icon.required' => __('The icon is required.'),
            'icon.image'    => __('Icon is must be an image.'),
            'icon.max'      => __('The icon may not be greater than 512 kilobytes in size.'),
            'link.required' => 'Link is required.',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }
        if ($request->hasFile('icon')) {
            $item = new DoctorSocialMedia();
            $item->doctor_id = $doctor->id;
            $item->link = $request->link;
            $file_name = uploadAndOptimizeImage(
                file: $request->icon,
            );
            $item->icon = $file_name;

            $item->save();
        }
        return response()->json(['status' => 'success', 'message' => __('Created Successfully')], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse {
        $doctor = auth()->guard('doctor_api')->user();
        $item = $doctor->socialMedia()->where('id', $id)->first();
        if (!$item) {
            return response()->json(['status' => 'error', 'message' => __('Not Found!')], 404);
        }
        $validator = Validator::make($request->all(), [
            'icon' => 'sometimes|image|max:512|mimes:jpeg,jpg,png,gif,webp,svg',
            'link' => 'required',
        ], [
            'icon.required' => __('The icon is required.'),
            'icon.image'    => __('Icon is must be an image.'),
            'icon.max'      => __('The icon may not be greater than 512 kilobytes in size.'),
            'link.required' => 'Link is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }

        $item->link = $request->link;

        if ($item && $request->hasFile('icon')) {
            $file_name = uploadAndOptimizeImage(
                file: $request->icon,
                oldFile: $item->icon
            );
            $item->icon = $file_name;
        }

        $item->save();

        return response()->json(['status' => 'success', 'message' => __('Updated successfully')], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse {
        $doctor = auth()->guard('doctor_api')->user();
        $item = $doctor->socialMedia()->where('id', $id)->first();
        if ($item) {
            if ($item->icon) {
                if (File::exists(public_path($item->icon))) {
                    unlink(public_path($item->icon));
                }
            }
            $item->delete();
            return response()->json(['status' => 'success', 'message' => __('Deleted Successfully')], 200);
        }
        return response()->json(['status' => 'error', 'message' => __('Not Found!')], 404);
    }

    public function statusUpdate($id): JsonResponse {
        $doctor = auth()->guard('doctor_api')->user();
        $item = $doctor->socialMedia()->where('id', $id)->first();
        if (!$item) {
            return response()->json(['status' => 'error', 'message' => __('Not Found!')], 404);
        }
        $status = $item->status == 1 ? 0 : 1;
        $item->update(['status' => $status]);
        return response()->json(['status' => 'success', 'message' => __('Updated successfully')]);
    }
}
