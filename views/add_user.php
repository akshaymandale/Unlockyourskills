<?php
// views/add_user.php
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="container add-user-container">
    <h1>Add User</h1>
    
    <!-- ✅ Tabs Section -->
    <div class="add-user-tabs">
        <div class="tab active" data-target="#basic-details">Basic Details</div>
        <div class="tab" data-target="#additional-details">Additional Details</div>
        <div class="tab" data-target="#extra-details">Extra Details</div>
    </div>

    <!-- ✅ Tab Content Section -->
    <div class="tab-content active" id="basic-details">
        <div class="form-group-row">
            <div class="form-group">
                <label>Profile ID (Auto-Generated)</label>
                <input type="text" name="profile_id" disabled class="input-field" placeholder="Generated Automatically">
            </div>
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required class="input-field">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required class="input-field">
            </div>
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label>Contact Number *</label>
                <input type="text" name="contact_number" required class="input-field">
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="input-field">
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="dob" class="input-field">
            </div>
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label>User Role *</label>
                <select name="user_role" required class="input-field">
                    <option value="Admin">Admin</option>
                    <option value="End User">End User</option>
                </select>
            </div>
            <div class="form-group">
                <label>Profile Expiry Date</label>
                <input type="date" name="profile_expiry" class="input-field">
            </div>
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label>User Status</label>
                <input type="checkbox" name="user_status" checked> Active
            </div>
            <div class="form-group">
                <label>Locked Status</label>
                <input type="checkbox" name="locked_status"> Unlocked
            </div>
            <div class="form-group">
                <label>Appear On Leaderboard</label>
                <input type="checkbox" name="leaderboard" checked> Yes
            </div>
        </div>
        <div class="form-group">
            <label>Profile Picture</label>
            <input type="file" name="profile_picture" accept="image/jpeg, image/png" class="input-field">
        </div>
    </div>
    
    <div class="tab-content" id="additional-details">
        <div class="form-group-row">
            <div class="form-group">
                <label>Country</label>
                <input type="text" name="country" class="input-field" id="country-autocomplete">
            </div>
            <div class="form-group">
                <label>State</label>
                <input type="text" name="state" class="input-field" id="state-autocomplete">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" class="input-field" id="city-autocomplete">
            </div>
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label>Timezone</label>
                <input type="text" name="timezone" class="input-field">
            </div>
            <div class="form-group">
                <label>Language</label>
                <input type="text" name="language" class="input-field">
            </div>
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label>Reports To</label>
                <input type="text" name="reports_to" class="input-field">
            </div>
            <div class="form-group">
                <label>Joining Date</label>
                <input type="date" name="joining_date" class="input-field">
            </div>
            <div class="form-group">
                <label>Retirement Date</label>
                <input type="date" name="retirement_date" class="input-field">
            </div>
        </div>
    </div>

    
    <div class="tab-content" id="extra-details">
        <div class="form-group-row">
            <div class="form-group">
                <label>Customised 1</label>
                <input type="text" name="customised_1" class="input-field">
            </div>
            <div class="form-group">
                <label>Customised 2</label>
                <input type="text" name="customised_2" class="input-field">
            </div>
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label>Customised 3</label>
                <input type="text" name="customised_3" class="input-field">
            </div>
            <div class="form-group">
                <label>Customised 4</label>
                <input type="text" name="customised_4" class="input-field">
            </div>
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label>Customised 5</label>
                <input type="text" name="customised_5" class="input-field">
            </div>
            <div class="form-group">
                <label>Customised 6</label>
                <input type="text" name="customised_6" class="input-field">
            </div>
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label>Customised 7</label>
                <input type="text" name="customised_7" class="input-field">
            </div>
            <div class="form-group">
                <label>Customised 8</label>
                <input type="text" name="customised_8" class="input-field">
            </div>
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label>Customised 9</label>
                <input type="text" name="customised_9" class="input-field">
            </div>
            <div class="form-group">
                <label>Customised 10</label>
                <input type="text" name="customised_10" class="input-field">
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>