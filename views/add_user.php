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

<div class="main-content">
    <div class="container add-user-container">
        <h1 class="page-title text-purple">
            <?= Localization::translate('add_user_title'); ?>
        </h1>
        <form action="index.php?controller=UserManagementController&action=storeUser" id="addUserForm" method="POST"
            enctype="multipart/form-data">
            <!-- ✅ Tabs Section -->
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" id="addUserTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#basic-details">
                        <?= Localization::translate('basic_details'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#additional-details">
                        <?= Localization::translate('additional_details'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#extra-details">
                        <?= Localization::translate('extra_details'); ?>
                    </a>
                </li>
            </ul>

            <!-- Tabs Content -->
            <input type="hidden" name="client_id" id="clientName" value="<?php echo htmlspecialchars($clientName); ?>">
            <div class="tab-content">
                <div class="tab-pane show active" id="basic-details">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('profile_id'); ?></label>
                            <input type="text" id="profile_id" name="profile_id" class="input-field" readonly>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('full_name'); ?> *</label>
                            <input type="text" id="full_name" name="full_name" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('email'); ?> *</label>
                            <input type="text" id="email" name="email" class="input-field">
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('contact_number'); ?> *</label>
                            <input type="text" id="contact_number" name="contact_number" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('gender'); ?></label>
                            <select name="gender" class="input-field">
                                <option value=""><?= Localization::translate('select_gender'); ?></option>
                                <option value="Male"><?= Localization::translate('male'); ?></option>
                                <option value="Female"><?= Localization::translate('female'); ?></option>
                                <option value="Other"><?= Localization::translate('other'); ?></option>
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('dob'); ?></label>
                            <input type="date" id="dob" name="dob" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('user_role'); ?> *</label>
                            <select id="user_role" name="user_role" class="input-field">
                                <option value=""><?= Localization::translate('select_user_role'); ?></option>
                                <option value="Admin"><?= Localization::translate('admin'); ?></option>
                                <option value="End User"><?= Localization::translate('end_user'); ?></option>
                                <option value="Instructor"><?= Localization::translate('instructor'); ?></option>
                                <option value="Corporate Manager"><?= Localization::translate('corporate_manager'); ?>
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('profile_expiry_date'); ?></label>
                            <input type="date" id="profile_expiry" name="profile_expiry" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('user_status'); ?> :</label>
                            <div>
                                <label>
                                    <input type="radio" name="user_status" value="Active" checked>
                                    <?= Localization::translate('active'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="user_status" value="Inactive">
                                    <?= Localization::translate('inactive'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('locked_status'); ?> :</label>
                            <div>
                                <label>
                                    <input type="radio" name="locked_status" value="1">
                                    <?= Localization::translate('locked'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="locked_status" value="0" checked>
                                    <?= Localization::translate('unlocked'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('leaderboard'); ?> :</label>
                            <div>
                                <label>
                                    <input type="radio" name="leaderboard" value="Yes">
                                    <?= Localization::translate('yes'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="leaderboard" value="No" checked>
                                    <?= Localization::translate('no'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('profile_picture'); ?></label>
                            <input type="file" id="profile_picture" name="profile_picture"
                                accept="image/jpeg, image/png" class="input-field">
                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="additional-details">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label for="country"><?= Localization::translate('country'); ?></label>
                            <select id="countrySelect" name="country" class="form-control">
                                <option value=""><?= Localization::translate('select_country'); ?></option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?= $country['id']; ?>"><?= $country['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label for="state"><?= Localization::translate('state'); ?></label>
                            <select id="stateSelect" name="state" class="form-control" disabled>
                                <option value=""><?= Localization::translate('select_state'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label for="city"><?= Localization::translate('city'); ?></label>
                            <select id="citySelect" name="city" class="form-control" disabled>
                                <option value=""><?= Localization::translate('select_city'); ?></option>
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('timezone'); ?></label>
                            <select id="timezoneSelect" name="timezone" class="input-field">
                                <option value=""><?= Localization::translate('select_timezone'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('language'); ?></label>
                            <input type="text" name="language" class="input-field">
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('reports_to'); ?></label>
                            <input type="text" name="reports_to" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('joining_date'); ?></label>
                            <input type="date" name="joining_date" class="input-field">
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('retirement_date'); ?></label>
                            <input type="date" name="retirement_date" class="input-field">
                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="extra-details">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('customised_1'); ?></label>
                            <input type="text" name="customised_1" class="input-field">
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('customised_2'); ?></label>
                            <input type="text" name="customised_2" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('customised_3'); ?></label>
                            <input type="text" name="customised_3" class="input-field">
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('customised_4'); ?></label>
                            <input type="text" name="customised_4" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('customised_5'); ?></label>
                            <input type="text" name="customised_5" class="input-field">
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('customised_6'); ?></label>
                            <input type="text" name="customised_6" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-lg-6 col-sm-12 form-group">
                            <label><?= Localization::translate('customised_7'); ?></label>
                            <input type="text" name="customised_7" class="input-field">
                        </div>
                        <div class="col-md-6 col-lg-6 col-sm-12 form-group">
                            <label><?= Localization::translate('customised_8'); ?></label>
                            <input type="text" name="customised_8" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-lg-6 col-sm-12 form-group">
                            <label><?= Localization::translate('customised_9'); ?></label>
                            <input type="text" name="customised_9" class="input-field">
                        </div>
                        <div class="col-md-6 col-lg-6 col-sm-12 form-group">
                            <label><?= Localization::translate('customised_10'); ?></label>
                            <input type="text" name="customised_10" class="input-field">
                        </div>
                    </div>
                </div>


                <div class="row mt-4">
                    <div class="col-md-12 form-actions">
                        <button type="submit" class="btn btn-primary"><?= Localization::translate('submit'); ?></button>
                        <button type="reset" class="btn btn-danger"><?= Localization::translate('cancel'); ?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- ✅ Form Validation Script -->
<script>
    const translations = <?= json_encode([
        "validation.full_name_required" => Localization::translate('validation.full_name_required'),
        "validation.email_required" => Localization::translate('validation.email_required'),
        "validation.email_invalid" => Localization::translate('validation.email_invalid'),
        "validation.contact_required" => Localization::translate('validation.contact_required'),
        "validation.contact_invalid" => Localization::translate('validation.contact_invalid'),
        "validation.dob_required" => Localization::translate('validation.dob_required'),
        "validation.dob_future" => Localization::translate('validation.dob_future'),
        "validation.user_role_required" => Localization::translate('validation.user_role_required'),
        "validation.profile_expiry_invalid" => Localization::translate('validation.profile_expiry_invalid'),
        "validation.image_format" => Localization::translate('validation.image_format'),
        "validation.image_size" => Localization::translate('validation.image_size')
    ]); ?>;
</script>


<script src="public/js/add_user_validation.js"></script>
<?php include 'includes/footer.php'; ?>