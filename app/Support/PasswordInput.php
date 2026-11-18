<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * Passwords are no longer run through the anti-XSS input filter (it used strip_tags, which cut
 * off everything after a "<" - so some emailed passwords never worked). Accounts whose password
 * was saved through the old filter still sign in: the filtered form is accepted once, then the
 * real password is saved in its place.
 */
class PasswordInput
{
    /** Form fields the filter must leave exactly as typed. */
    public const FIELDS = ['password', 'password_confirmation', 'current_password', 'confirm_password', 'new_password', 'old_password', 'smtp_password', 'mail_password'];

    public static function verify(Model $user, ?string $plain): bool
    {
        $plain = (string) $plain;
        $hash = (string) $user->getAuthPassword();
        if ($plain === '' || $hash === '') return false;
        if (Hash::check($plain, $hash)) return true;
        $legacy = self::legacyFiltered($plain);
        if ($legacy !== $plain && $legacy !== '' && Hash::check($legacy, $hash)) {
            $user->forceFill(['password' => Hash::make($plain)])->save();
            return true;
        }
        return false;
    }

    /** What PreventXSSAttackMiddleware used to turn a typed password into. */
    public static function legacyFiltered(string $value): string
    {
        $value = strip_tags(str_replace(['&lt;', '&gt;'], '', $value), '<span><p><a><b><i><u><strong><br><hr><table><tr><th><td><ul><ol><li><h1><h2><h3><h4><h5><h6><del><ins><sup><sub><pre><address><img><figure><embed><iframe><video><style>');
        foreach (['onload', 'onunload', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout', 'onkeypress', 'onkeydown', 'onkeyup', 'onfocus', 'onblur', 'onsubmit', 'onreset', 'onchange', 'onselect', 'oninput', 'oncontextmenu'] as $attribute) {
            $value = str_replace($attribute, '', $value);
        }
        return $value;
    }
}
