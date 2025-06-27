<?php require_once __DIR__ . '/../core/UrlHelper.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Unlock Your Skills</title>
    <link rel="stylesheet" href="<?= UrlHelper::url('public/css/style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="<?= UrlHelper::url('public/images/favicon.ico') ?>">
</head>
<body class="login-body">

<div class="login-container">
    <div class="login-box">
        <!-- Left Section with Logo -->
        <div class="login-left">
            <img src="<?= UrlHelper::url('public/images/UYSlogo.png') ?>" alt="Logo">
        </div>

        <!-- Right Section with Login Form -->
        <div class="login-right">
            <h2 class="login-title">Unlock Your Skills</h2>

            <!-- Login Form -->
            <form id="loginForm" action="index.php?controller=LoginController&action=login" method="POST">
                <div class="form-group">
                    <input type="text" id="client_code" name="client_code" class="login-input"
                           placeholder="Enter Client Code (e.g., ACME_CORP)">
                    <div class="error-message" id="client_code_error"></div>
                </div>

                <div class="form-group">
                    <input type="text" id="username" name="username" class="login-input"
                           placeholder="Enter Email or Profile ID">
                    <div class="error-message" id="username_error"></div>
                </div>

                <div class="form-group">
                    <input type="password" id="password" name="password" class="login-input"
                           placeholder="Enter Password">
                    <div class="error-message" id="password_error"></div>
                </div>

                <div class="error-message" id="general_error"></div>

                <button type="submit" class="login-button" id="loginBtn">
                    <span id="loginBtnText">Login</span>
                    <span id="loginSpinner" class="spinner" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <?php if (isset($ssoEnabled) && $ssoEnabled && !empty($ssoProviders)): ?>
            <!-- SSO Section -->
            <div class="sso-section">
                <div class="divider">
                    <span>Or login with</span>
                </div>
                <div class="sso-providers">
                    <?php foreach ($ssoProviders as $provider): ?>
                    <a href="index.php?controller=LoginController&action=ssoLogin&client_code=<?= urlencode($clientCode ?? ''); ?>&provider=<?= urlencode($provider['provider_name']); ?>"
                       class="sso-btn sso-<?= strtolower(str_replace(' ', '-', $provider['provider_name'])); ?>">
                        <i class="fas fa-sign-in-alt"></i>
                        Login with <?= htmlspecialchars($provider['provider_name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="forgot-password">
                <a href="#">Forgot Password?</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= UrlHelper::url('public/js/login.js') ?>"></script>

</body>
</html>
