<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//echo '<pre>'; print_r($_SESSION);
// ✅ Get Client Name from Session
$clientName = $_SESSION['client_code'] ?? 'DEFAULT';
// Fetch all countries for the initial dropdown
require_once 'config/database.php';

// Instantiate the Database class
$database = new Database();

// Establish the connection
$db = $database->connect();

// Check if the connection was successful
if (!$db) {
    die("Database connection failed.");
}

$stmt = $db->query("SELECT id, name FROM countries");
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>
<input type="hidden" id="clientName" value="<?php echo htmlspecialchars($clientName); ?>">
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
    <form action="process_add_user.php" id="addUserForm" method="POST" enctype="multipart/form-data">
        <div class="tab-content">
            <div class="tab-pane show active" id="basic-details">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Profile ID (Auto-Generated)</label>
                        <input type="text" id="profile_id" name="profile_id" class="input-field" readonly>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Email *</label>
                        <input type="text" id="email" name="email" required class="input-field">
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Contact Number *</label>
                        <input type="text" id="contact_number" name="contact_number" required class="input-field">
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
                        <input type="date" id="dob" name="dob" class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>User Role *</label>
                        <select id="user_role" name="user_role" required class="input-field">
                        <option value="">Select User Role</option>
                            <option value="Admin">Admin</option>
                            <option value="End User">End User</option>
                            <option value="Instructor">Instructor</option>
                            <option value="Corporate Manager">Corporate Manager</option>
                        </select>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Profile Expiry Date</label>
                        <input type="date" id="profile_expiry" name="profile_expiry" class="input-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>User Status : </label>
                        <input type="checkbox" name="user_status_active" checked> Active
                        <input type="checkbox" name="user_status_inactive" > InActive
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Locked Status : </label>
                        <input type="checkbox" name="locked_status"> Locked
                        <input type="checkbox" name="unlocked_status" checked> Unlocked
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Appear On Leaderboard : </label>
                        <input type="checkbox" name="leaderboard_yes" checked> Yes
                        <input type="checkbox" name="leaderboard_no"> No
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg, image/png" class="input-field">
                    </div>
         </div>
            </div>

            <div class="tab-pane" id="additional-details">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label for="country">Country</label>
                        <select id="countrySelect" name="country" class="form-control">
                            <option value="">Select Country</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?= $country['id']; ?>"><?= $country['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label for="state">State</label>
                            <select id="stateSelect" name="state" class="form-control" disabled>
                                <option value="">Select State</option>
                            </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label for="city">City</label>
                            <select id="citySelect" name="city" class="form-control" disabled>
                                <option value="">Select City</option>
                            </select>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                        <label>Timezone</label>
                            <select id="timezoneSelect" name="timezone" class="input-field">
                                <option value="">Select Timezone</option>
                            </select>
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
<!-- ✅ Form Validation Script -->
<script src="public/js/add_user_validation.js"></script>
<?php include 'includes/footer.php'; ?>