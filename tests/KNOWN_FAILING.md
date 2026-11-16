# Known failing tests

These 20 tests were already failing before the CI pipeline was added. They are tagged
`#[Group('known-failing')]` and the pipeline runs `php artisan test --exclude-group known-failing`,
so they do not block deployments. Everything else must pass before code is deployed.

| Tests | Why they fail |
|---|---|
| `Auth/EmailVerificationTest`, `Auth/PasswordConfirmationTest`, `Auth/PasswordResetTest`, `Auth/PasswordUpdateTest`, `ProfileTest`, `ExampleTest` | Stock Laravel Breeze tests for the patient account area. Patient accounts are disabled on MedFind, and the pages they render need site settings that the test database does not seed. Delete or rewrite them for the provider portals. |
| `DoctorPortalRegressionTest::test_doctor_free_coupon_needs_no_payment_reference` | Uses the retired `doctor.subscription.store` route. 100%-discount coupons currently have no checkout path (Paystack rejects a zero amount). |
| `SupplierDirectoryWorkflowTest` (3 tests) | Public directory pages need seeded site settings; quotation update now redirects instead of returning 404. |

When you fix one, remove its `known-failing` tag so the pipeline starts guarding it.
