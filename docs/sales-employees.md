# Sales employees

Sign in as the owner (a super administrator), open **Employees → Invite Sales employees / upload emails**, then paste addresses or upload a CSV. CSV headers are `email,name`; name is optional. Each batch accepts up to 100 employees. Existing addresses and invalid batches are rejected before accounts are created.

The system assigns **Sales**, generates a random temporary password, and queues an email containing the admin login link and password. Employees sign in with their own email at `/admin/login`, change their password on first login, and then access the Sales dashboard. Temporary passwords expire after seven days. Invitation status appears on the invite page; the owner can send a new password for pending invitations. Resending invalidates the old temporary password. Completed accounts are managed through Employees.

Mail delivery needs the configured SMTP service and a running queue worker or the existing scheduled queue worker (`php artisan schedule:run` every minute). Queue jobs containing temporary credentials are encrypted. Sent means accepted by the configured mail transport, not a guarantee of inbox delivery. Delivery errors appear as failed after retries. A superseded or expired invitation is not sent.

Sales can view provider lists and onboard specialists, facilities, schools and suppliers with subscription selection and Paystack invitations. Account creation records identify the employee. Sales cannot modify website content/settings, access messages/newsletters/contacts, manage employees/roles, verify providers, review payments, issue refunds or delete providers. These restrictions apply to direct URLs even if extra role permissions are accidentally granted.

Existing non-super-administrator accounts were assigned Sales by migration `2026_10_14_000008_add_sales_employee_invitations`. Owner accounts retain their access. The owner's role assignment screen can also assign the Sales role, but new email invitations assign it automatically. No employee emails are sent until the owner submits invitations.
