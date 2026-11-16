# Admin client onboarding

Public site styles and designs are unchanged. Payment options and required account setup behavior have been updated.

## Employee permissions

Doctor, facility, school and supplier groups have View, Create, Translate, Store, Edit, Update and Delete permissions. Create opens onboarding and Store submits the account. School/supplier Translate and Edit remain reserved for future management screens. Payment history requires `client.payment.view`; Paystack refunds require `client.payment.refund` and existing settings update access. Manual payment confirmation is disabled. Reports require `account.report.view`.

## Paystack checkout and activation

1. Open the provider listing and select Add. Enter contact details and choose the subscription plan.
2. The client receives a Paystack checkout invitation. Account status remains inactive until verified payment.
3. Paystack verification checks the stored reference, account email, currency and server-calculated amount. Client-supplied amounts are ignored. Signed webhooks and the verified return callback settle the same payment idempotently.
4. After confirmation, the account activates and login credentials are emailed. The generated password is encrypted while pending and removed after successful email delivery.
5. Client Payment Reviews has an Open / Retry Paystack Checkout action for pending attempts. Failed invitation delivery does not activate the account.

All four provider types use Paystack for signup and renewal. Checkout pages have no manual-payment forms. Other checkout gateways are blocked, and admin payment settings expose Paystack only. Historical paid records are retained. Pending manual admin-onboarding attempts have been converted to Paystack; other pending manual attempts have been retired. Existing confirmation-role grants have been migrated to refund permissions.

Coupons are calculated on the server. A pending attempt is reused when the account, plan and coupon match, avoiding duplicate invoices and repeated coupon consumption. Paystack checkout requires a positive total; zero-cost plans or fully discounted coupons are rejected before creating a charge.

## Mandatory account setup

After login, incomplete clients are redirected to profile setup, then document upload. They cannot use the dashboard or operational account features until all three requirements are satisfied:

- Required contact and professional/business details.
- Profile photo for a specialist; logo/profile image for other provider types.
- A verification document in Pending or Approved state. Rejected documents require resubmission.

Profile editing, document submission, account security, billing and logout remain accessible. Document submission completes onboarding; professional verification remains a separate admin decision. School/supplier publication requires completed onboarding. Doctor API operations are also gated and return setup links for incomplete accounts.

## Reports and dashboard

Account creation reports preserve creator ID/name/email, account type, source and creation date, with filters and CSV export. Unknown historical creators are marked Unknown. The dashboard has date/type filters, interactive charts, staff activity and review queues. Confirmed KES revenue counts mirrored legacy/shared records once and remains available after account deletion.

Deleting a facility, school or supplier removes access and listings, cancels shared subscriptions/pending attempts, unpublishes supplier products and retains financial/creation history. A late verified Paystack receipt is recorded without reactivating the deleted account.

## Deployment

Run `php artisan migrate --force` on other installations. Configure Paystack status/keys in admin payment settings, and configure SMTP and the Laravel scheduler. Run `php artisan schedule:run` every minute; the existing schedule processes queued invitations and activation jobs. Undelivered activation emails are retried by `clients:retry-activation` every five minutes.

To install missing default plans without overwriting custom prices or inactive plans, run `php artisan db:seed --class="Modules\Doctor\database\seeders\SubscriptionPackageSeeder" --force`. Repeated runs do not duplicate plans. Checkout and the admin plan list share the same plan source. All provider types use one coupon controller and table.