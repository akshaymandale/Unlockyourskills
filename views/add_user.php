<?php
// views/add_user.php
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="container add-user-container">
    <h1>Add User</h1>
    
    <!-- ✅ Tabs Section -->
    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs" id="addUserTabs">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#basic-details">Basic Details</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#additional-details">Additional Details</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#extra-details">Extra Details</a>
        </li>
    </ul>

        <!-- Tabs Content -->
    <form action="process_add_user.php"" method="POST" enctype="multipart/form-data">
        <div class="tab-content">
            <div class="tab-pane show active" id="basic-details">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Profile ID (Auto-Generated)</label>
                        <input type="text" name="profile_id" disabled class="input-field" placeholder="Generated Automatically">
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" required class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required class="input-field">
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Contact Number *</label>
                        <input type="text" name="contact_number" required class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Gender</label>
                        <select name="gender" class="input-field">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>User Role *</label>
                        <select name="user_role" required class="input-field">
                            <option value="Admin">Admin</option>
                            <option value="End User">End User</option>
                        </select>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Profile Expiry Date</label>
                        <input type="date" name="profile_expiry" class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>User Status</label>
                        <input type="checkbox" name="user_status" checked> Active
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Locked Status</label>
                        <input type="checkbox" name="locked_status"> Unlocked
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Appear On Leaderboard</label>
                        <input type="checkbox" name="leaderboard" checked> Yes
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Profile Picture</label>
                        <input type="file" name="profile_picture" accept="image/jpeg, image/png" class="input-field">
                    </div>
                </div>
            </div>

            <div class="tab-pane" id="additional-details">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Country</label>
                        <input type="text" name="country" class="input-field" id="country-autocomplete">
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>State</label>
                        <input type="text" name="state" class="input-field" id="state-autocomplete">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>City</label>
                        <input type="text" name="city" class="input-field" id="city-autocomplete">
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Timezone</label>
                        <input type="text" name="timezone" class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Language</label>
                        <input type="text" name="language" class="input-field">
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Reports To</label>
                        <input type="text" name="reports_to" class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Joining Date</label>
                        <input type="date" name="joining_date" class="input-field">
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Retirement Date</label>
                        <input type="date" name="retirement_date" class="input-field">
                    </div>
                </div>
            </div>

            <div class="tab-pane" id="extra-details">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Customised 1</label>
                        <input type="text" name="customised_1" class="input-field">
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Customised 2</label>
                        <input type="text" name="customised_2" class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Customised 3</label>
                        <input type="text" name="customised_3" class="input-field">
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Customised 4</label>
                        <input type="text" name="customised_4" class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Customised 5</label>
                        <input type="text" name="customised_5" class="input-field">
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Customised 6</label>
                        <input type="text" name="customised_6" class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Customised 7</label>
                        <input type="text" name="customised_7" class="input-field">
                    </div>
                    <div class="col-md-6 col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Customised 8</label>
                        <input type="text" name="customised_8" class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Customised 9</label>
                        <input type="text" name="customised_9" class="input-field">
                    </div>
                    <div class="col-md-6 col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Customised 10</label>
                        <input type="text" name="customised_10" class="input-field">
                    </div>
                </div>
            </div>
            <div class="row mt-4">
    <div class="col-md-12 form-actions">
        <button type="submit" class="btn btn-primary">Submit</button>
        <button type="reset" class="btn btn-danger">Cancel</button>
    </div>
</div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>