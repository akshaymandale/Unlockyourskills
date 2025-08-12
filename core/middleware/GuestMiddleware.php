<?php

require_once 'core/Middleware.php';
require_once 'core/UrlHelper.php';

/**
 * Guest Middleware
 * Ensures user is NOT logged in (for login/register pages)
 */
class GuestMiddleware extends Middleware
{
    public function handle()
    {
        // If user is already logged in, redirect to dashboard
        if (isset($_SESSION['id']) && isset($_SESSION['user'])) {
            $this->redirect(UrlHelper::url('dashboard'));
            return false;
        }
        
        return true;
    }
}
