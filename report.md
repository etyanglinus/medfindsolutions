# Provider Portals and Capabilities Report

**Prepared:** 24 September 2026  
**Scope:** Current specialist, facility, healthcare school, and medical supplier dashboards and provider workflows in this codebase.  
**Product framing:** MedFind is a healthcare visibility and discovery platform. It is not being treated as a patient-management or appointment-booking product in this assessment.

## Executive summary

The platform has four provider account types, but their portal maturity differs considerably:

- **Specialist portal:** Profile management, working schedule, visibility analytics, payment history, licence documents, enquiries/reviews, admin notifications, and legacy appointment-related functions. Its dashboard is still heavily shaped around appointments and earnings. Some appointment and withdrawal routes remain even though their sidebar items were removed.
- **Facility portal:** A clinic account can manage a basic listing, subscription, gallery/branches, licensing documents, enquiries/reviews, profile views, and admin notifications. Some profile fields are controlled by plan entitlements. This is the most visibility-oriented of the two older portals, though dashboard copy still refers to patients.
- **Healthcare school portal:** Registration, email verification, plan/payment entry, status summary, document submissions, and admin messages are present. The resend endpoint exists but no login-page resend form was found. The provider-facing dashboard is only a basic status page. School profile editing and course/intake management are not connected.
- **Medical supplier portal:** Registration, email verification, plan/payment entry, status summary, document submissions, and admin messages are present. The resend endpoint exists but no login-page resend form was found. The provider-facing dashboard is only a basic status page. Supplier profile editing, product/catalogue management, and quote management are not connected.

The admin can review school/supplier profiles and documents and can message each provider type. The profile action currently allows publishing after approval without checking that verification is complete, so the admin process must be careful and the code should enforce both states. Public course and supplier pages exist as frontend shells, but their directory data is not yet fully connected. The project should remain in staging until the provider CRUD, payment lifecycle, data reconciliation, security, and regression-test work in the production checklist is complete.

## Capability comparison

| Capability | Specialist | Facility (Clinic) | Healthcare school | Medical supplier |
|---|---|---|---|---|
| Separate authenticated account/guard | Yes | Yes | Yes | Yes |
| Dashboard | Yes; appointments/earnings plus profile views | Yes; subscription plus profile views | Basic profile/subscription status | Basic profile/subscription status |
| Edit provider profile | Yes | Yes, subject to subscription entitlements | No provider editor found | No provider editor found |
| Subscription and payment view | Legacy plan/payment screens | Legacy plan/payment screens | Latest shared subscription summary; checkout/payment entry | Latest shared subscription summary; checkout/payment entry |
| Visibility analytics | Profile view total and last 30 days | Profile view total and last 30 days | Not present | Not present |
| Verification document upload | Yes | Yes | Yes | Yes |
| Admin notification thread | Yes | Yes | Yes | Yes |
| Public enquiries/quotes | Inquiry/review inbox route exists | Inquiry/review inbox route exists | No school inquiry workflow found | Quote-request tables exist, but supplier quote workflow not connected |
| Courses or products management | Not applicable | Gallery/branches content tools | Course model/table exists; no provider CRUD flow found | Product/category models and tables exist; no provider CRUD flow found |

## Specialist portal

### What the specialist can do

The specialist signs into a dedicated portal under the `doctor` guard. The dashboard controller scopes profile-view analytics and any legacy booking/earnings queries to the signed-in specialist. Current dashboard information includes:

- Profile-view total and a last-30-days trend chart.
- Legacy appointment totals, today’s appointments, and appointment earnings.
- Links to edit professional details, working schedule, payment history, and admin notifications.
- Licence submission and history, including licensing body, registration number, expiry date, file, and review status.
- Provider inquiry and review inbox screens.
- Optional social-link management when enabled in settings.

The profile editor is based on the existing doctor profile/translation schema. Registration collects name, professional licensing body, specialty, phone, email, password, and optional insurer data. Specialty and licensing choices now draw from the Kenyan reference lists seeded in the prior work. A verification email is attempted and a resend path exists, but the specialist login controller does not check `email_verified_at`; verification is therefore not enforced at sign-in.

### Product-fit gaps

The dashboard and some copy still present appointment booking, treatment, and earnings as the primary specialist workflow. The specialist sidebar no longer shows appointments, Zoom, leave, withdrawals, or the old patient message menu, but hiding links does not remove their routes or code. Several of those routes remain accessible directly if authorization allows. For a visibility-only product, replace the booking-first dashboard with listing completeness, profile views, contact clicks/enquiries, verification state, publication status, and plan/renewal status; retire or explicitly disable the unrelated routes after deciding whether any still serve another product use case.

The specialist portal displays profile-view numbers, but the report should not assume that contact conversion or search impressions are tracked. Those metrics need their own event definitions and storage before they can be reported.

## Facility portal

Here “facility” means the authenticated `Clinic` account used by the portal. It is distinct from the legacy/public `Facility` directory model and table, which coexist in the codebase and still need production data reconciliation. The clinic account schema has verification fields and an authenticated resend endpoint, but registration does not send a verification message and clinic login does not enforce verified email.

### What a facility can do

The clinic portal currently provides:

- Dashboard cards for the current plan, duration, payment status, and profile views.
- Profile-view count and a 30-day trend chart.
- Clinic profile editing for name, specialty/type, phone, Nairobi location, and plan-enabled fields such as WhatsApp, operating hours, accepted insurance, maps, description, equipment, and featured services.
- Gallery and branch management.
- Licence/accreditation upload and document history.
- Inquiry/review inbox.
- Admin notifications and replies.
- Subscription status and password changes.

The `Clinic` profile editor checks for an active subscription and filters optional fields against plan entitlements. Facilities can submit one of the seeded facility licensing bodies such as KMPDC, PPB, KMLTTB, KNRA, or KNBTTS when uploading a document. The legacy clinic Paystack path marks the subscription approved and sets the clinic status active after payment verification; that can activate public visibility before a separate admin profile/licence decision. Treat this as a launch blocker until approval and publication are separated from payment.

### Product-fit gaps

Some dashboard copy says “patients” and the profile editor forces the location to Nairobi. Those should be aligned with the intended visibility audience and county coverage model. The dashboard currently shows profile views, but not directory impressions, search placement, clicks to call/WhatsApp, or lead volume. The public display of all profile fields and plan-driven ranking must be confirmed before charging providers for those benefits.

## Healthcare school portal

### What a school can do today

A school account is created through the shared provider registration page. Registration creates a `HealthcareInstitution` record plus a `ProviderProfile`, then sends a signed email-verification link. The signed link is time-limited; resend verification rotates the token. Login checks that email verification has been completed. After registration the flow provides plan/payment selection. The minimal school/supplier dashboard shows:

- Profile review status.
- Latest subscription/package and its payment status.
- Original and discounted price (“Was” / “Now”) and coupon code where stored.
- A link to verification documents and a link to the public course section.
- Admin notifications and a reply screen.

The school can upload a PDF/JPG/PNG licence or accreditation document (up to 10 MB) with licensing body, registration number, verification body, and expiry date. The private file is stored on the local storage disk. Admin review can approve/reject it and save a note; a review history table records status changes. The resend endpoint exists, but the public login view has no visible resend-verification form, so account recovery from a missed/expired verification email is incomplete for the provider.

### Missing school capabilities

There is no provider-facing school profile editor, no course create/edit/publish workflow, and no intake/application information management screen. Although a healthcare course model and table exist, the public pages and account dashboard do not yet give the school a complete way to manage that data. There are also no school analytics or enquiry-management tools found.

## Medical supplier portal

### What a supplier can do today

A supplier account uses its own guard and follows the school-style registration, email verification, payment, status, document, and admin-message flow. Its minimal dashboard shows profile status and subscription details and links to the public supplier section and verification-document screen. Supplier licence/accreditation documents use the same private upload and admin review workflow as school documents. The resend endpoint exists, but the public login view has no visible resend-verification form, so account recovery from a missed/expired verification email is incomplete for the provider.

### Missing supplier capabilities

The supplier cannot edit the company profile from the dashboard, manage product listings, assign categories/subcategories, maintain catalogue files/images, or handle quotation requests in a provider inbox. Supplier/product/category/quotation data models and tables exist, but model/schema availability should not be mistaken for usable CRUD workflows. The public supplier directory is still a frontend scaffold and is not a complete data-backed marketplace.

## Admin operations relevant to providers

The admin has separate screens for school/supplier profile status actions (approve, verify, publish, request changes, suspend) and provider-document review. A provider must be approved before publishing through the profile screen. The seeded licensing bodies, insurer list, services, and specialist types are maintained through reference-data seeders. Subscription coupons can be restricted by provider type, plan, eligible email, validity window, and usage limit.

Admin-to-provider messaging now supports specialists, facilities, schools, and suppliers. Messages are stored in a provider-specific conversation and the send action attempts an email notification. Email delivery still depends on correctly configured production mail credentials and a working mail service; database persistence does not itself prove that a notification email reached the provider.

## What to tell a provider who asks “what can I do here?”

- **Specialists:** “You can create and maintain your listing, provide your licence details, set a working schedule, see profile views, review enquiries, and contact MedFind administration. Some legacy booking screens remain in the system; we are aligning the portal with visibility reporting.”
- **Facilities:** “You can maintain your clinic listing and media, submit licence documents, see profile views, receive enquiries, review your plan, and contact MedFind administration.”
- **Schools:** “You can register, verify your email, choose a plan, submit verification documents, and receive admin notifications. Course and school-profile self-service is not yet available, so listing publication requires the MedFind team.”
- **Suppliers:** “You can register, verify your email, choose a plan, submit verification documents, and receive admin notifications. Product and catalogue self-service is not yet available, so listing publication requires the MedFind team.”

## Evidence and limits

This report is a source-code capability review dated 24 September 2026, not a live production acceptance test. It is based on current routes, controllers, models, migrations, and Blade views. The named route and syntax checks performed during the recent sidebar/messaging work do not establish that every portal workflow succeeds end to end. No new tests were run for this report. See `onboarding-and-production-readiness.md` for the launch decision and onboarding steps.
