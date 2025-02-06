<?php
// views/login.php
?>

<?php include 'includes/header.php'; ?>

<div class="login-container">
    <div class="login-box">
        <!-- Left Section with Logo -->
        <div class="login-left">
            <img src="public/images/logo.png" alt="Logo">
        </div>

        <!-- Right Section with Login Form -->
        <div class="login-right">
            <h2 class="login-title">Unlock Your Skills</h2>
            <form action="index.php?controller=LoginController&action=login" method="POST">
                <input type="text" name="client_code" class="login-input" placeholder="Client Code" required>
                <input type="text" name="username" class="login-input" placeholder="Username" required>
                <input type="password" name="password" class="login-input" placeholder="Password" required>
                <button type="submit" class="login-button">Login</button>
            </form>
            <div class="forgot-password">
                <a href="#">Forgot Password?</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
