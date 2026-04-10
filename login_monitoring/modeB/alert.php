<?php
function show_alert($status) {
    if ($status === "WARNING") {
        echo "
        <div class='security-alert'>
            ⚠️ SECURITY ALERT<br>
            Suspicious login behavior detected.
        </div>
        ";
    }
}
