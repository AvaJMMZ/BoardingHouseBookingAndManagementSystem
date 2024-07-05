<?php
    session_start();
    @include '../conn/config.php';
    $errors = array();

    // Check if the user is not logged in or not a landlord, redirect to login page
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'landlord') {
        header("Location: ../Registration/frm_login.php");
        exit();
    }

    // Get the user details from the session
    $username = $_SESSION['username'];
    $userType = $_SESSION['user_type'];
    $email = $_SESSION['email'];

    // Check if the user clicked the "Logout" link
    if (isset($_GET['logout'])) {
        // Destroy the session to log the user out
        session_destroy();

        // Redirect the user back to the login page
        header("Location: ../Registration/frm_login.php");
        exit();
    }

    // Check if the landlord has set up their account
    $userId = $_SESSION['user_id'];
    $checkSetupQuery = "SELECT landlord_id FROM owner_about WHERE landlord_id = ?";
    $stmt = mysqli_prepare($conn, $checkSetupQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $isAccountSetup = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    // Fetch landlord information from owner_about table
    $landlordInfoQuery = "SELECT * FROM owner_about WHERE landlord_id = ?";
    $stmt = mysqli_prepare($conn, $landlordInfoQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $landlordInfo = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Check if the landlord has set up their Boarding House
    $userId = $_SESSION['user_id'];
    $checkSetupQuery = "SELECT landlord_id FROM bh_info WHERE landlord_id = ?";
    $stmt = mysqli_prepare($conn, $checkSetupQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $isBHSetup = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    // Fetch landlord information from bh_info table
    $bhInfoQuery = "SELECT * FROM bh_info WHERE landlord_id = ?";
    $stmt = mysqli_prepare($conn, $bhInfoQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $bhInfo = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (isset($bhInfo["bh_id"])) {
        $_SESSION['bh_id'] = $bhInfo["bh_id"];
    }
    
    // Adding Boarding House
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit'])) {
        // Validate and sanitize the form data
        $businessName = mysqli_real_escape_string($conn, $_POST['business_name']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $landmark = mysqli_real_escape_string($conn, $_POST['landmark']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $gmapLink = mysqli_real_escape_string($conn, $_POST['gmap_link']);

        $licenseFilePath = NULL; 
        $bhImgFilePath = NULL; 

        if (isset($_FILES['license']) && $_FILES['license']['error'] === UPLOAD_ERR_OK) {
            // Process the license image upload
            $uploadDir = "License/";
            $licenseFileName = basename($_FILES['license']['name']);
            $licenseFilePath = $uploadDir . $licenseFileName;
            move_uploaded_file($_FILES['license']['tmp_name'], $licenseFilePath);
        }

        if (isset($_FILES['bh_img']) && $_FILES['bh_img']['error'] === UPLOAD_ERR_OK) {
            // Process the boarding house image upload
            $uploadDir = "BHPhotos/"; 
            $bhImgFileName = basename($_FILES['bh_img']['name']);
            $bhImgFilePath = $uploadDir . $bhImgFileName;
            move_uploaded_file($_FILES['bh_img']['tmp_name'], $bhImgFilePath);
        }

        // Insert the data into bh_info table
        $insertQuery = "INSERT INTO bh_info (landlord_id, business_name, location, landmark, description, gmap_link, license, bh_img)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($stmt, "isssssss", $userId, $businessName, $location, $landmark, $description, $gmapLink, $licenseFilePath, $bhImgFilePath);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Set a success message to display 
        $_SESSION['success-message'] = "Boarding House information added successfully.";
        
        // Redirect to the same page to prevent form resubmission
        header("Location: pg_bh.php");
        exit();
    }

    //Editing and Updating Boarding House info
    if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['action'] === 'edit') {
        $businessName = mysqli_real_escape_string($conn, $_POST['business_name']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $landmark = mysqli_real_escape_string($conn, $_POST['landmark']);
        $noRooms = intval($_POST['no_rooms']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $gmapLink = mysqli_real_escape_string($conn, $_POST['gmap_link']);
    
        // Update the data in bh_info table
        $updateQuery = "UPDATE bh_info SET business_name = ?, location = ?, landmark = ?, description = ?, gmap_link = ? WHERE landlord_id = ?";
        $stmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($stmt, "sssssi", $businessName, $location, $landmark, $description, $gmapLink, $userId);
        $result = mysqli_stmt_execute($stmt);
    
        if ($result === false) {
            // Error occurred, set an error message
            $errors[] = "Failed to update boarding house information.";
        } else {
            // Set a success message to display after editing
            $_SESSION['success-message'] = "Boarding House information updated successfully.";
        }
    
        mysqli_stmt_close($stmt);
    
        // Redirect to the same page to prevent form resubmission
        header("Location: pg_bh.php");
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_image'])) {
        $newBHImgFilePath = NULL;
    
        if (isset($_FILES['new_bh_image']) && $_FILES['new_bh_image']['error'] === UPLOAD_ERR_OK) {
            // Process the new BH image upload
            $uploadDir = "BHPhotos/"; // Replace with your image upload directory
            $newBHImgFileName = basename($_FILES['new_bh_image']['name']);
            $newBHImgFilePath = $uploadDir . $newBHImgFileName;
            move_uploaded_file($_FILES['new_bh_image']['tmp_name'], $newBHImgFilePath);
        }
    
        // Update the 'bh_img' field in the database
        $updateImageQuery = "UPDATE bh_info SET bh_img = ? WHERE landlord_id = ?";
        $stmt = mysqli_prepare($conn, $updateImageQuery);
        mysqli_stmt_bind_param($stmt, "si", $newBHImgFilePath, $userId); // Assuming $userId is the current landlord's ID
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    
        // Set a success message to display 
        $_SESSION['success-message'] = "Boarding House image updated successfully.";
        
        // Redirect to the same page to prevent form resubmission
        header("Location: pg_bh.php");
        exit();
    }
    
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_license_image'])) {
        $newLicenseImgFilePath = NULL;

        if (isset($_FILES['new_license_image']) && $_FILES['new_license_image']['error'] === UPLOAD_ERR_OK) {
            // Process the new license image upload
            $uploadDir = "License/"; // Replace with your image upload directory
            $newLicenseImgFileName = basename($_FILES['new_license_image']['name']);
            $newLicenseImgFilePath = $uploadDir . $newLicenseImgFileName;
            move_uploaded_file($_FILES['new_license_image']['tmp_name'], $newLicenseImgFilePath);
        }

        // Update the 'license' field in the database
        $updateLicenseQuery = "UPDATE bh_info SET license = ? WHERE landlord_id = ?";
        $stmt = mysqli_prepare($conn, $updateLicenseQuery);
        mysqli_stmt_bind_param($stmt, "si", $newLicenseImgFilePath, $userId); // Assuming $userId is the current landlord's ID
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Set a success message to display
        $_SESSION['success-message'] = "License image updated successfully.";

        // Redirect to the same page to prevent form resubmission
        header("Location: pg_bh.php");
        exit();
    }
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Boarding House</title>
        <link rel="stylesheet" type="text/css" href="../Style/style_bh.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script>
            function todescription(str) {
                return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
            }

            function showTutorialModal() {
                document.getElementById("tutorialmodal").style.display = "flex";
            }

            function hideTutorialModal() {
                document.getElementById("tutorialmodal").style.display = "none";
            }

            function closeSuccessMessage() {
                var successMessage = document.querySelector('.success-message');
                successMessage.style.display = 'none';
            }

            function closeErrorMessage() {
                var successMessage = document.querySelector('.error-message');
                successMessage.style.display = 'none';
            }

            // Check if the bhInfo contains Google Maps link
            <?php if ($isBHSetup && isset($bhInfo['gmap_link'])) : ?>
                // Get the Google Maps link from the bhInfo
                const gmapLink = "<?php echo $bhInfo['gmap_link']; ?>";

                // Get the map container element
                const mapContainer = document.getElementById('map-container');

                // Set the Google Maps link as the innerHTML of the map container
                mapContainer.innerHTML = gmapLink;
            <?php endif; ?>

            function validateGMapLink() {
                var gmapLink = document.getElementById('gmap_link').value.trim();
                var validPrefix = 'https://www.google.com/maps/embed?';

                if (gmapLink.startsWith(validPrefix)) {
                    return true;
                } else {
                    alert('Please provide a valid Google Maps embed link starting with ' + validPrefix);
                    return false;
                }
            }

            function showBHEdit() {
                document.getElementById("bhedit").style.display = "flex";
            }

            function hideBHEdit() {
                document.getElementById("bhedit").style.display = "none";
            }

            function showBHImageModal() {
                document.getElementById("bhImageModal").style.display = "flex";
                const profileImage = document.getElementById("profile_image");
                const bhImageModal = document.getElementById("profile_image_modal");
                bhImageModal.src = profileImage.src;
            }

            function hideBHImageModal() {
                document.getElementById("bhImageModal").style.display = "none";
            }

            document.getElementById("bhImageForm").addEventListener("submit", function (e) {
                // Check if a new image is selected before submitting the form
                var bhImageInput = document.getElementById("bh_image_input_modal");
                if (bhImageInput.files.length === 0) {
                    e.preventDefault();
                    alert("Please select a new image before saving.");
                }
            });

            document.getElementById("bh_image_input_modal").addEventListener("change", function () {
                // Display the selected image in the modal
                previewBHImageInModal("bh_image_input_modal");
            });

            function previewBHImageInModal(inputId) {
                var input = document.getElementById(inputId);
                var bhImageModal = document.getElementById("bh_image_modal");

                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        bhImageModal.src = e.target.result;
                    };

                    reader.readAsDataURL(input.files[0]);
                }
            }

            // License

            function showLicenseModal() {
                document.getElementById("licenseModal").style.display = "flex";
                const licenseImage = document.getElementById("license_image");
                const licenseImageModal = document.getElementById("license_image_modal");
                licenseImageModal.src = licenseImage.src;
            }

            function hideLicenseModal() {
                document.getElementById("licenseModal").style.display = "none";
            }

            document.getElementById("licenseImageForm").addEventListener("submit", function (e) {
                // Check if a new image is selected before submitting the form
                var licenseImageInput = document.getElementById("license_image_input_modal");
                if (licenseImageInput.files.length === 0) {
                    e.preventDefault();
                    alert("Please select a new image before saving.");
                }
            });

            document.getElementById("license_image_input_modal").addEventListener("change", function () {
                // Display the selected image in the modal
                previewLicenseImageInModal("license_image_input_modal");
            });

            function previewLicenseImageInModal(inputId) {
                var input = document.getElementById(inputId);
                var licenseImageModal = document.getElementById("license_image_modal");

                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        licenseImageModal.src = e.target.result;
                    };

                    reader.readAsDataURL(input.files[0]);
                }
            }
        </script>
    </head>
    <body>
        <nav>
            <div class="logo">BH for HOME</div>
        </nav>
        <div class="alert-messages">
            <?php
                if (isset($_SESSION['success-message'])) {
                    echo '<div class="success-message">
                            <span class="close-btn" onclick="closeSuccessMessage()">&times;</span>'
                            . $_SESSION['success-message'] .
                        '</div>';
                    unset($_SESSION['success-message']);
                }

                // Display error messages
                if (!empty($errors)) {
                    echo '<div class="error-message">
                            <span class="close-btn" onclick="closeErrorMessage()">&times;</span>';
                    foreach ($errors as $error) {
                        echo '<p>' . $error . '</p>';
                    }
                    echo '</div>';
                }
            ?>
        </div>

        <div class="main">
            <div class="sidebar">
                <div class="owner-profile" style="border-radius: 0 0 50% 0;">
                    <a href="pg_landlord.php" style="background-color: transparent;">
                        <div class="profile" style="width: 70px;  height:70px;">
                            <?php if (!empty($landlordInfo['landlord_image'])) : ?>
                                <img id="profile_image" src="<?php echo $landlordInfo['landlord_image']; ?>" alt="profile">
                            <?php else : ?>
                                <img id="profile_image" src="../images/noimage.jfif" alt="profile">
                            <?php endif; ?>
                        </div>
                    </a>
                    <h3><?php echo $username; ?></h3>
                    <p>username</p> 
                </div>
                

                <button class="sidebar-btn" style="background-color: #f2f2f2; color: #140C06;" onclick="location.href='pg_bh.php'"><i class="fas fa-home"></i> Boarding House</button>
                <button class="sidebar-btn" style="border-radius: 0 50% 0 0;" onclick="location.href='pg_mytenant.php'"><i class="fas fa-users"></i> Tenants</button>
                <div class="space">.</div>
                <a href="?logout=1">Logout</a>
            </div>

            <div class="main-content">
                <?php if (!$isBHSetup) : ?>
                    <div class="bh-form">
                        <h3>You currently do not have a boarding house. Kindly provide the necessary information to add your boarding house.</h3>

                        <form action="pg_bh.php" method="post" enctype="multipart/form-data" onsubmit="return validateGMapLink()">
                            <input type="hidden" name="action" value="add">
                            <div style="display: flex;">
                                <div style="flex: 1;">
                                    <div class="textbox">
                                        <label for="business_name">Business Name:</label>
                                        <input type="text" id="business_name" name="business_name" placeholder="Enter Your Business Name" required><br>
                                    </div>
                                    <div class="textbox">
                                        <label for="location">Location:</label>
                                        <input type="text" id="location" name="location" placeholder="Street, Purok, Barangay, Municipality, Province" required><br>
                                    </div>
                                    <div class="textbox">
                                        <label for="gmap_link">Google Maps Link:</label>
                                        <div class="how-link">
                                            <a href="javascript:void(0);" onclick="showTutorialModal()">How to get link?</a>
                                        </div>
                                        <textarea id="gmap_link" name="gmap_link" rows="4" cols="50" maxlength="500" placeholder="https://www.google.com/maps/embed?..." required></textarea><br>
                                    </div>
                                </div>
                                <div style="flex: 1;">
                                    <div class="textbox">
                                        <label for="landmark">Landmark: <span style="font-style: italic;">(optional)</span></label>
                                        <input type="text" id="landmark" name="landmark" placeholder="Enter Landmark"><br>
                                    </div>
                                    <div class="textbox">
                                        <label for="license">License (Image):</label>
                                        <input type="file" id="license" name="license" accept="image/*" required><br>
                                    </div>
                                    <div class="textbox">
                                        <label for="bh_img">BH Image:</label>
                                        <input type="file" id="bh_img" name="bh_img" accept="image/*" required><br>
                                    </div>
                                </div>
                            </div>
                            <div class="textbox">
                                <label for="description">Description:</label>
                                <textarea id="description" name="description" rows="4" cols="50" maxlength="500" placeholder="Tell us about your boarding house..." required></textarea>
                            </div>
                            <div class="button">
                                <input type="submit" name="submit" value="Add Boarding House">
                            </div>
                        </form>
                    </div>

                    <!-- How to get Google Maps Link -->
                    <div class="tutorial-modal" id="tutorialmodal" style="display: none;">
                        <div class="tutorial-modal-content">
                            <span class="close" onclick="hideTutorialModal()">&times;</span>
                            <h2>How to Get Link?</h2>
                            <ol>
                                <li>Open Google Maps</li>
                                <li>Choose your location</li>
                                <li>Click Share</li>
                                <li>Select "Embed a Map"</li>
                                <li>Copy only the <strong>LINK</strong> inside the quote</li>
                            </ol>
                            <p style="text-align: center;">Example:</p>
                            <p style="word-wrap: break-word;">https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d833.5395601285592!2d124.05092119157018!3d6.557774334361216!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e1!3m2!1sen!2sph!4v1691229313939!5m2!1sen!2sph</p>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="bhName">
                        <?php if ($isBHSetup) : ?>
                            <p onclick="location.href='pg_room.php';" style="cursor: pointer;" class="btn-room">Manage Rooms</p>
                            <h1><?php echo $bhInfo['business_name']; ?> &#183; <span style="font-style: italic; font-weight:lighter;">#<?php echo $bhInfo['bh_id']; ?></span></h1>
                            <i class="fas fa-edit edit-icon" href="javascript:void(0);" onclick="showBHEdit()"></i>
                        <?php else : ?>
                            <p>NONE</p>
                        <?php endif; ?>
                    </div>

                    <div class="bhInfo">
                        <div class="bhimage-con" onclick="showBHImageModal()">
                            <img id="bh_image" src="<?php echo $bhInfo['bh_img']; ?>" alt="Boarding House Image">
                        </div>
                        <div class="la-in-con">
                            <div style="display: flex;">
                                <div class="label">
                                    <label for="location">Location:</label>
                                    <label for="landmark">Landmark:</label>
                                    <label for="no_rooms">Number of Rooms:</label>
                                    <label for="lisence">License:</label>
                                    <label for="description">Description:</label>
                                </div>
                                <div class="info">
                                    <?php if ($isBHSetup) : ?>
                                        <p><?php echo $bhInfo['location']; ?></p>
                                        <p><?php echo $bhInfo['landmark']; ?></p>
                                        <p><?php echo $bhInfo['no_rooms']; ?></p>
                                        <div class="button">
                                            <button onclick="showLicenseModal()" class="btn-view-license">View License</button>
                                        </div>
                                    <?php else : ?>
                                        <p>None</p>
                                        <p>None</p>
                                        <p>None</p>
                                        <p>None</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="description">
                                <p id="description"><?php echo $bhInfo['description']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="gmap">
                        <p>Google Map: </p>
                        <div class="map">
                            <iframe src="<?php echo $bhInfo['gmap_link']; ?>" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Edit Boarding House Info -->
            <div class="bh-edit" id="bhedit" style="display: none;">
                <div class="bh-edit-content">
                    <span class="close" onclick="hideBHEdit()">&times;</span>
                    <h3>YOUR BOARDING HOUSE DETAILS</h3>

                    <form action="pg_bh.php" method="post" enctype="multipart/form-data" onsubmit="return validateGMapLink()">
                        <input type="hidden" name="action" value="edit"> <!-- Add this line for editing a boarding house -->
                        <input type="hidden" name="bh_id" value="<?php echo $bhInfo['bh_id']; ?>"> <!-- Include the boarding house ID -->

                        <div style="display: flex;">
                            <div style="flex: 1;">
                                <div class="textbox">
                                    <label for="business_name">Business Name:</label>
                                    <input type="text" id="business_name" name="business_name" placeholder="Enter Your Business Name" value="<?php echo $bhInfo['business_name']; ?>" required><br>
                                </div>
                                <div class="textbox">
                                    <label for="location">Location:</label>
                                    <input type="text" id="location" name="location" placeholder="Street, Purok, Barangay, Municipality, Province" value="<?php echo $bhInfo['location']; ?>" required><br>
                                </div>
                                <div class="textbox">
                                    <label for="gmap_link">Google Maps Link:</label>
                                    <div class="how-link">
                                        <a href="javascript:void(0);" onclick="showTutorialModal()">How to get link?</a>
                                    </div>
                                    <textarea id="gmap_link" name="gmap_link" rows="4" cols="50" maxlength="500" placeholder="https://www.google.com/maps/embed?..." required><?php echo $bhInfo['gmap_link']; ?></textarea><br>
                                </div>
                            </div>
                            <div style="flex: 1;">
                                <div class="textbox">
                                    <label for="landmark">Landmark: <span style="font-style: italic;">(optional)</span></label>
                                    <input type="text" id="landmark" name="landmark" placeholder="Enter Landmark" value="<?php echo $bhInfo['landmark']; ?>"><br>
                                </div>
                            </div>
                        </div>
                        <div class="textbox">
                            <label for="description">Description:</label>
                            <textarea id="description" name="description" rows="4" cols="50" maxlength="500" placeholder="Tell us about your boarding house..." required><?php echo $bhInfo['description']; ?></textarea>
                        </div>
                        <div class="button">
                            <input type="submit" name="save" value="Save">
                        </div>
                    </form>
                </div>
            </div>

            <!-- How to get Google Maps Link -->
            <div class="tutorial-modal" id="tutorialmodal" style="display: none;">
                <div class="tutorial-modal-content">
                    <span class="close" onclick="hideTutorialModal()">&times;</span>
                    <h2>How to Get Link?</h2>
                    <ol>
                        <li>Open Google Maps</li>
                        <li>Choose your location</li>
                        <li>Click Share</li>
                        <li>Select "Embed a Map"</li>
                        <li>Copy only the <strong>LINK</strong> inside the quote</li>
                    </ol>
                    <p style="text-align: center;">Example:</p>
                    <p style="word-wrap: break-word;">https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d833.5395601285592!2d124.05092119157018!3d6.557774334361216!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e1!3m2!1sen!2sph!4v1691229313939!5m2!1sen!2sph</p>
                </div>
            </div>
            
            <!-- Modal for BH Image -->
            <div class="bhimage-modal" id="bhImageModal" style="display: none;">
                <div class="bhimage-modal-content">
                    <h3>Boarding House Image</h3>
                    <form action="pg_bh.php" method="post" enctype="multipart/form-data" id="bhImageForm">
                        <div class="textbox" style="display: flex; align-items: center; flex-direction: column;">
                            <div class="bhimage">
                                <img id="bh_image_modal" src="<?php echo $bhInfo['bh_img']; ?>" alt="Boarding House Image">
                            </div>
                            <label for="bh_image_input_modal" class="btn-change-image">CHANGE</label>
                            <input type="file" id="bh_image_input_modal" name="new_bh_image" onchange="previewBHImageInModal('bh_image_input_modal')" style="display: none;">
                        </div>

                        <div class="button-con">
                            <button type="submit" name="change_image">Save</button>
                            <button type="button" onclick="hideBHImageModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal for License Image -->
            <div class="license-modal" id="licenseModal" style="display: none;">
                <div class="license-modal-content">
                    <h3>License Image</h3>
                    <form action="pg_bh.php" method="post" enctype="multipart/form-data" id="licenseImageForm">
                        <div class="textbox" style="display: flex; align-items: center; flex-direction: column;">
                            <div class="license-image" style="display: flex; flex-direction:column;" >
                                <img id="license_image_modal" src="<?php echo $bhInfo['license']; ?>" alt="License Image">
                                <a href="<?php echo $bhInfo['license']; ?>" target="_blank">Full View</a>
                            </div>
                            <label for="license_image_input_modal" class="btn-change-image">CHANGE</label>
                            <input type="file" id="license_image_input_modal" name="new_license_image" onchange="previewLicenseImageInModal('license_image_input_modal')" style="display: none;">
                        </div>

                        <div class="button-con">
                            <button type="submit" name="change_license_image">Save</button>
                            <button type="button" onclick="hideLicenseModal()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <footer class="footer">
            &copy; 2023 Boarding House Booking. All rights reserved.
        </footer>
    </body>
</html>