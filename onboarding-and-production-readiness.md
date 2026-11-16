# Provider Onboarding and Production Readiness

**Prepared:** 24 September 2026  
**Decision:** Keep the platform in staging. It is not production-ready for a broad public launch.

## 1. Provider onboarding today

### Before inviting a provider

1. Confirm the provider type and the correct contact person.
2. Prepare the information they will need: official name, contact email/phone, Nairobi or county location, professional/facility registration details, logo/images, and supporting licence/accreditation documents.
3. Confirm that the required plan is active and its price, duration, and entitlements are correct in admin.
4. If applying a coupon, confirm that its provider type, plan, eligible email, active dates, and usage limit match the intended recipient.
5. Ensure production email delivery has been configured and tested before asking anyone to rely on verification or notification emails.

### Specialists

1. Direct the specialist to the public provider registration page: `/provider/register?type=specialist` (the route name is `provider.register`). The shared page is currently gated by the specialist-registration setting; confirm that registration is enabled.
2. The specialist submits name, email, phone, password, specialty, and professional licensing body. Insurer information may also be selected.
3. Complete the verification and subscription/payment steps shown by the live form. A verification email is attempted, but the specialist login controller does not enforce verified email.
4. Legacy Paystack verification currently marks the specialist subscription approved and changes the specialist status to active. Hold production use until admin verification/publication is separated from payment, and inspect licence evidence before treating a listing as verified.
5. The specialist signs in through the specialist tab on `/login`, then can edit their profile, schedule, submit documents, and monitor profile views.
6. Since the specialist dashboard still contains legacy appointment/earnings information, explain that these are legacy functions and confirm they match the service being offered before onboarding at scale.

### Facilities / clinics

1. Direct the facility to `/provider/register?type=clinic` or the facility-registration link on `/login`.
2. The facility provides its clinic name, specialty/type, contact details, professional licensing body, and account credentials.
3. The facility registration/login flow does not currently enforce email verification. Legacy Paystack verification approves the subscription and activates the clinic account; hold production use until admin approval/publication is independent of payment. The clinic account has profile-edit fields gated by plan entitlements.
4. The facility signs into the Facility tab on `/login`, updates available profile fields, and uses Gallery & Branches and License Verification.
5. Current Paystack behavior may activate clinic visibility before document review. Until corrected, do not onboard paid clinics as if admin approval is a prerequisite; pause Paystack/live exposure or manually suppress their public listing. Check accepted-insurance claims, maps, services, and operating hours for accuracy.
6. Its portal is at `/clinic`; the dashboard shows the current legacy subscription and tracked profile views.

### Healthcare schools

1. Direct the school to `/provider/register?type=school` (or `/partner/school/register`, which redirects to the shared registration page).
2. The school submits its institution name, official email, phone, and password, then continues to package selection.
3. Ask the school to open the signed verification email promptly. Verification links expire after 60 minutes. The resend endpoint is rate-limited, but the login page does not currently show a resend form; if the email is missed, support will need to resolve it until a self-service resend UI is added.
4. The school submits the selected plan payment. Bank transfer submissions remain pending admin review. A Paystack success is checked against amount, KES currency, and account email, but payment does not automatically publish or verify the school.
5. Admin reviews the school profile under **Schools & Suppliers**, checks licence documents under **Provider Documents → Schools**, and explicitly approves/verifies/publishes when requirements are met.
6. The school can sign into `/login` using the School tab, see account/subscription status, submit or review documents, and receive admin notifications. It cannot yet self-manage school profile or course/intake records in the portal.

### Medical suppliers

1. Direct the supplier to `/provider/register?type=supplier` (or `/partner/supplier/register`, which redirects to the shared registration page).
2. The supplier submits company name, official email, phone, and password, then continues to package selection.
3. Ask them to complete the signed email verification within 60 minutes. The resend endpoint is rate-limited, but the login page does not currently show a resend form; if the email is missed, support will need to resolve it until a self-service resend UI is added.
4. Review the selected plan/payment. Bank-transfer entries need admin approval; Paystack verification checks successful status, amount, KES currency, and supplier email. Payment must not be treated as listing verification or publication.
5. Admin reviews the supplier under **Schools & Suppliers**, reviews documents under **Provider Documents → Suppliers**, and explicitly approves/verifies/publishes the listing.
6. The supplier can sign into `/login` using the Supplier tab, see status/plan information, submit documents, and receive admin notifications. It cannot yet self-manage product listings, categories, catalogues, or quotation requests.

## 2. Admin onboarding checklist

For each provider, record a clear review trail outside informal email where required by the business:

- Confirm account email and phone with the provider.
- Check the legal/display name and county/location.
- Inspect registration number, licensing body, verification body, and expiry date.
- Download the private document through the admin review screen; do not copy it to a public URL.
- Record what was checked and why it was accepted/rejected.
- Confirm payment amount, plan, duration, provider type, and any coupon discount.
- Use profile approval and document verification as separate decisions.
- Publish only after approval and verification checks are complete.
- Send the provider an admin message with missing items or next steps.
- Recheck profile visibility on the public directory after publishing.

The current profile review screen has status actions, but its “request changes” note is not persisted to the profile or a dedicated review-history table. The publish action checks approval status but does not require verified status. For regulated provider review, preserve an auditable decision record and require approval plus verification before publication.

## 3. Production readiness assessment

### Current positives

- Provider account types and separate authentication guards exist for specialists, clinics, schools, and suppliers.
- School/supplier registration, email verification, a rate-limited resend endpoint, signed-link expiry, and verified-login checks are implemented in code. The public login UI does not expose the resend endpoint.
- Provider profiles, subscription/payment ledger, coupon support, licensing reference data, and private provider-document storage are present.
- Admin profile review and document review screens exist.
- Admin-to-provider message threads support all four provider types; email notification depends on live mail configuration.
- Basic profile-view analytics are present for specialists and clinics.

### Blocking gaps

1. **Provider self-service and public data:** School profile/course CRUD and supplier profile/product/category/catalogue/quotation workflows are missing. Public course and supplier pages are not yet fully wired to live data. Specialist and facility discovery analytics need meaningful visibility and enquiry measures beyond view counts.
2. **Payment lifecycle:** The shared ledger and Paystack/bank-transfer flows exist, but all configured gateways are not integrated consistently. Webhook callbacks and signature verification, complete idempotency coverage, failed payments/refunds, automatic expiry/grace handling, renewal paths, and production payment reconciliation remain outstanding. Legacy specialist/clinic Paystack handlers approve subscriptions and activate the listing/account automatically; change this so payment does not bypass admin verification/publication.
3. **Legacy data:** No production clone audit/backfill is documented. Reconcile clinics, facilities, doctors, and legacy subscriptions before migration. Decide the fate of the separate `facilities` table. Run `php artisan providers:sync-registry --dry-run` on a restored clone first.
4. **Verification and review controls:** Document approval works, but provider-specific review forms, durable admin notes for profile changes, full audit history, bulk actions, and expiry operations still need completion. Publishing a profile only checks approval, not verification. Specialist login does not enforce email verification; clinic signup and login do not enforce it either. Schools/suppliers enforce verification at login but lack a resend form in the login UI.
5. **Automated regression coverage:** The handover record identifies failing authentication feature tests and missing coverage for payment idempotency, expiry, admin review, publishing/suspension, and provider authorization. Do not treat passing syntax or route-list checks as a substitute for the test suite.
6. **Security:** Review production debug state, rotate the SMTP credential previously flagged in the handover, confirm the application key, protect installer/database-clear/destructive admin functions, enforce rate limits, audit guards/policies, and validate private file handling.
7. **Operations:** Complete staging acceptance, restore tests for database and storage, HTTPS and permissions review, queue and scheduler setup, monitoring, recurring backups and retention, cache rebuild instructions, and tested rollback procedure.
8. **Visibility-product fit:** Specialist dashboard is still appointment/earnings-first, and facility copy still refers to patients. Remove or disable unused appointment/Zoom/withdrawal workflows intentionally and shift provider dashboards toward visibility, clicks, enquiries, verification, and renewal metrics.

### Launch decision

**Not production-ready yet. Keep it in staging.** Registration and core review foundations are useful, but the school/supplier provider tools, public directory data, payment callbacks/lifecycle, production data reconciliation, security review, and end-to-end regression tests are material launch requirements. A local seeded demo is not evidence of production data integrity or payment readiness.

## 4. Recommended launch sequence

1. Confirm provider journeys and plan rules with the business owner.
2. Decide the final visibility metrics and remove unused appointment-era surfaces.
3. Finish school/supplier profile, course/product, quote, and relevant enquiry workflows; connect the public directory pages to real provider records.
4. Finish admin review notes/history and licensing document expiry handling.
5. Complete and test payment callbacks, signatures, idempotency, failures/refunds, renewals, expiry/grace periods, and subscription reconciliation.
6. Restore a production database clone into isolated staging; audit providers/subscriptions and trial the registry sync dry run.
7. Fix the failing tests, then add the missing authorization, payment, review, and provider journey coverage. Run the full suite against the intended production-like database engine.
8. Perform a security review and rotate any exposed credentials; verify private files cannot be reached publicly.
9. Run staging acceptance with representative Kenyan specialists, facilities, schools, and suppliers.
10. Verify database/storage backup restoration, queues, scheduler, HTTPS, monitoring, retention, and rollback.
11. Approve deployment only after all blockers have evidence of completion.

## 5. Scope and caveat

This is a source-code and prior handover assessment dated 24 September 2026. The project was not given a live production-clone audit in this task, no production payment was made, no verification email was delivered, and no full test suite was run for this report. Reassess the decision after the above items are completed on a staging clone.
