<?php

/**
 * Some shared hosts (including MedFind's cPanel server) disable php_uname() in php.ini.
 * The Anthropic PHP SDK calls it, unqualified, only to report the server's CPU type in a
 * request header, so Tafuta failed on every message. When the real function is missing, this
 * namespaced stand-in answers instead; PHP resolves the SDK's call to it before the global one.
 */

namespace Anthropic\Core;

if (!\function_exists('php_uname') && !\function_exists(__NAMESPACE__.'\php_uname')) {
    function php_uname(string $mode = 'a'): string
    {
        return $mode === 'm' ? 'unknown' : \PHP_OS_FAMILY;
    }
}
