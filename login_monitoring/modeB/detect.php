<?php
function getDeviceInfo() {
    $agent = $_SERVER['HTTP_USER_AGENT'];

    if (preg_match('/mobile/i', $agent)) $device = "Mobile";
    elseif (preg_match('/tablet/i', $agent)) $device = "Tablet";
    else $device = "Desktop";

    if (preg_match('/windows/i', $agent)) $os = "Windows";
    elseif (preg_match('/android/i', $agent)) $os = "Android";
    elseif (preg_match('/iphone|ipad/i', $agent)) $os = "iOS";
    else $os = "Other";

    if (preg_match('/chrome/i', $agent)) $browser = "Chrome";
    elseif (preg_match('/edge/i', $agent)) $browser = "Edge";
    elseif (preg_match('/safari/i', $agent)) $browser = "Safari";
    else $browser = "Other";

    return [$device, $os, $browser];
}
