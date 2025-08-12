<?php
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session status: " . session_status() . "\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";
echo "Session cookie params: " . print_r(session_get_cookie_params(), true) . "\n";
?> 