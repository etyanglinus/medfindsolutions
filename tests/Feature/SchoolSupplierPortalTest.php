<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SchoolCourseReviewController;
use App\Http\Controllers\Provider\PartnerPortalController;
use App\Models\Admin;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitution;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class SchoolSupplierPortalTest extends TestCase
{
    use RefreshDatabase;

    private function partner(string $type)
    {
        $model = $type === 'school' ? HealthcareInstitution::class : MedicalSupplier::class;
        $record = $model::create(['name' => ucfirst($type), 'slug' => $type, 'official_email' => "$type@example.test", 'password' => bcrypt('x'), 'email_verified_at' => now()]);
        $profile = ProviderProfile::create(['provider_type' => $type, 'provider_id' => $record->id, 'status' => 'published', 'published_at' => now()]);
        $record->update(['provider_profile_id' => $profile->id]);
        ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => 'premium', 'package_name' => 'Premium', 'amount' => 5000, 'currency' => 'KES', 'duration_months' => 3, 'status' => 'approved', 'starts_at' => now()->subDay(), 'expires_at' => now()->addMonths(3)]);
        $this->actingAs($record, $type);
        return $record;
    }

    private function admin(): Admin
    {
        $admin = Admin::where('email', 'owner@example.test')->first() ?? tap(new Admin, fn ($a) => $a->forceFill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('x'), 'status' => 'active', 'is_super_admin' => 1])->save());
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    private function req(array $data, array $files = []): Request
    {
        $request = Request::create('/x', 'POST', $data, [], $files);
        $request->setLaravelSession(app('session.store'));
        return $request;
    }

    public function test_product_photo_is_saved_with_the_product(): void
    {
        Storage::fake('public');
        $supplier = $this->partner('supplier');

        app(PartnerPortalController::class)->saveProduct($this->req(['name' => 'Patient monitor'], ['image' => UploadedFile::fake()->image('monitor.png', 400, 300)]), 'supplier');

        $product = $supplier->products()->sole();
        $this->assertNotNull($product->image, 'The stored image path used to be discarded before saving.');
        $this->assertStringStartsWith('supplier-products/'.$supplier->id.'/', $product->image);
        Storage::disk('public')->assertExists($product->image);
        $this->assertSame('pending', $product->approval_status);
        $this->assertFalse((bool) $product->is_published);
    }

    public function test_course_review_flow_with_note_resubmission_and_approval(): void
    {
        $school = $this->partner('school');
        app(PartnerPortalController::class)->saveCourse($this->req(['name' => 'Diploma in Nursing', 'fees' => 'KES 120,000']), 'school');
        $course = $school->courses()->sole();
        $this->assertSame('pending', $course->approval_status);

        $this->admin();
        $review = app(SchoolCourseReviewController::class);
        $review->update($this->req(['approval_status' => 'changes_required', 'admin_note' => 'Add the intake dates.']), $course);
        $this->assertSame('changes_required', $course->fresh()->approval_status);
        $this->assertSame('Add the intake dates.', $course->fresh()->admin_note);

        // A decision other than approval needs a note.
        try { $review->update($this->req(['approval_status' => 'rejected']), $course); $this->fail('A rejection without a note must be refused.'); }
        catch (\Illuminate\Validation\ValidationException) { $this->assertSame('changes_required', $course->fresh()->approval_status); }

        // The school edits and resubmits: back to pending, old note cleared.
        $this->actingAs($school, 'school');
        app(PartnerPortalController::class)->saveCourse($this->req(['name' => 'Diploma in Nursing', 'intake_starts_at' => '2027-01-10', 'intake_ends_at' => '2027-02-10']), 'school', $course);
        $this->assertSame('pending', $course->fresh()->approval_status);
        $this->assertNull($course->fresh()->admin_note);

        $this->admin();
        $review->update($this->req(['approval_status' => 'approved']), $course);
        $this->assertSame('approved', $course->fresh()->approval_status);
    }

    public function test_course_review_page_lists_waiting_courses_and_needs_permission(): void
    {
        $school = $this->partner('school');
        $school->courses()->create(['name' => 'Waiting course', 'slug' => 'waiting', 'approval_status' => 'pending']);
        $school->courses()->create(['name' => 'Live course', 'slug' => 'live', 'approval_status' => 'approved']);

        $this->admin();
        $view = app(SchoolCourseReviewController::class)->index(Request::create('/admin/school-courses'));
        $this->assertSame(['Waiting course'], $view->getData()['courses']->pluck('name')->all());

        $employee = new Admin;
        $employee->forceFill(['name' => 'Employee', 'email' => 'employee@example.test', 'password' => bcrypt('x'), 'status' => 'active', 'is_super_admin' => 0])->save();
        $this->actingAs($employee, 'admin');
        $this->expectException(\App\Exceptions\AccessPermissionDeniedException::class);
        app(SchoolCourseReviewController::class)->index(Request::create('/admin/school-courses'));
    }
}
