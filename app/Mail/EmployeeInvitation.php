<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;
class EmployeeInvitation extends Mailable
{
    public function __construct(public string $employeeName, public string $temporaryPassword, public string $loginUrl) {}
    public function build() { return $this->subject('Your MedFind Sales employee account')->view('emails.employee-invitation'); }
}
