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
    $landlordID = $_SESSION['user_id'];
    $checkSetupQuery = "SELECT landlord_id FROM owner_about WHERE landlord_id = ?";
    $stmt = mysqli_prepare($conn, $checkSetupQuery);
    mysqli_stmt_bind_param($stmt, "i", $landlordID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $isAccountSetup = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    // Fetch landlord information from owner_about table
    $landlordInfoQuery = "SELECT * FROM owner_about WHERE landlord_id = ?";
    $stmt = mysqli_prepare($conn, $landlordInfoQuery);
    mysqli_stmt_bind_param($stmt, "i", $landlordID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $landlordInfo = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Fetch landlord information from bh_info table
    $bhInfoQuery = "SELECT * FROM bh_info WHERE landlord_id = ?";
    $stmt = mysqli_prepare($conn, $bhInfoQuery);
    mysqli_stmt_bind_param($stmt, "i", $landlordID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $bhInfo = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (isset($bhInfo["bh_id"])) {
        $_SESSION['bh_id'] = $bhInfo["bh_id"];
    }

    // Check if the form is submitted for updating landlord information
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_info'])) {
        // Check if a file was uploaded
        if (isset($_FILES['landlord_image']) && $_FILES['landlord_image']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['landlord_image']['name'];
            $file_tmp = $_FILES['landlord_image']['tmp_name'];
            $file_size = $_FILES['landlord_image']['size'];
            $file_type = $_FILES['landlord_image']['type'];

            // Move the uploaded file to a permanent location
            $destination = 'ProfilePhotos/' . $file_name;
            if (move_uploaded_file($file_tmp, $destination)) {
                // Update the database with the new image path
                $updateImageQuery = "UPDATE owner_about SET landlord_image = ? WHERE landlord_id = ?";
                $stmt = mysqli_prepare($conn, $updateImageQuery);
                mysqli_stmt_bind_param($stmt, "si", $destination, $landlordID);
                $isUpdateImageSuccessful = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                if ($isUpdateImageSuccessful) {
                    $_SESSION['upload_success'] = "Image uploaded successfully.";
                    header("Location: pg_landlord.php");
                    exit();
                } else {
                    $errors[] = "Failed to update image. Please try again later.";
                }
            } else {
                $errors[] = "Failed to upload the image.";
            }
        }

        // Process the rest of the form data to update landlord information
        $firstname = $_POST['firstname'];
        $middlename = $_POST['middlename'];
        $lastname = $_POST['lastname'];
        $contact_no = $_POST['contact_no'];
        $gender = $_POST['gender'];
        $address = $_POST['address'];
        $birthdate = $_POST['birthdate'];

        // Update landlord information in the database
        $updateInfoQuery = "UPDATE owner_about SET firstname=?, middlename=?, lastname=?, contact_no=?, gender=?, address=?, birthdate=? WHERE landlord_id=?";
        $stmt = mysqli_prepare($conn, $updateInfoQuery);
        mysqli_stmt_bind_param($stmt, "sssssssi", $firstname, $middlename, $lastname, $contact_no, $gender, $address, $birthdate, $landlordID);
        $isUpdateSuccessful = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($isUpdateSuccessful) {
            $_SESSION['upload_success'] = "Your information is updated successfully.";
            header("Location: pg_landlord.php");
            exit();
        } else {
            $errors[] = "Failed to update information. Please try again later.";
        }
    }

    // Check if the form is submitted for changing the password
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        // Get the old password, new password, and confirmation from the form
        $oldPassword = $_POST['old_password'];
        $newPassword = $_POST['new_password'];

        // Validate the new password and confirmation
        if ($newPassword !== $_POST['confirm_password']) {
            $errors[] = "New password and confirmation do not match.";
        }

        // Check if the old password matches the one stored in the database
        $checkPasswordQuery = "SELECT password FROM user_info WHERE username = ?";
        $stmt = mysqli_prepare($conn, $checkPasswordQuery);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $storedPassword);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if (!password_verify($oldPassword, $storedPassword)) {
            $errors[] = "Incorrect old password.";
        }

        if (empty($errors)) {
            // Hash the new password before storing it in the database
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update the password in the user_info table
            $updatePasswordQuery = "UPDATE user_info SET password = ? WHERE username = ?";
            $stmt = mysqli_prepare($conn, $updatePasswordQuery);
            mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $username);
            $isUpdatePasswordSuccessful = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($isUpdatePasswordSuccessful) {
                $_SESSION['password_change_success'] = "Password changed successfully.";
                header("Location: pg_landlord.php");
                exit();
            } else {
                $errors[] = "Failed to change password. Please try again later.";
            }
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Landlord Page</title>
        <link rel="stylesheet" type="text/css" href="../Style/style_landlord.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script>
            // Show the modal when the page loads if account setup is not done
            document.addEventListener('DOMContentLoaded', function () {
                <?php if (!$isAccountSetup) : ?>
                    var modal = document.getElementById("setupAccountModal");
                    modal.style.display = "block";
                <?php endif; ?>
            });

            // Function to show the account setup form
            function showAccountSetupForm() {
                document.getElementById("setupAccountModal").style.display = "none";
                document.getElementById("accountSetupForm").style.display = "block";
            }

            // Function to hide the account setup form
            function hideAccountSetupForm() {
                document.getElementById("accountSetupForm").style.display = "none";
                document.getElementById("setupAccountModal").style.display = "block";
            }

            // Function to preview and update the selected image
            function previewImage() {
                const fileInput = document.getElementById("image_input");
                const profile = document.getElementById("profile_image");
                const welcomeProfile = document.getElementById("welcome_profile_image");

                if (fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        profile.src = e.target.result; 
                        welcomeProfile.src = e.target.result;
                    };

                    reader.readAsDataURL(fileInput.files[0]);
                }
            }

            function showEditModal() {
                document.getElementById("editModal").style.display = "flex";
            }

            function hideEditModal() {
                document.getElementById("editModal").style.display = "none";
            }

            // Function to allow changing the image
            function changeImage() {
                document.getElementById("image_input").click();
            }

            // Function to allow changing the image
            function changeImageModal() {
                document.getElementById("profile_image_form").submit();
            }

            // Function to show the profile image modal
            function showProfileModal() {
                document.getElementById("profileModal").style.display = "flex";
                const profileImage = document.getElementById("profile_image");
                const profileModal = document.getElementById("profile_image_modal");
                profileModal.src = profileImage.src;
            }

            // Function to hide the profile image modal
            function hideProfileModal() {
                document.getElementById("profileModal").style.display = "none";
            }

            function previewImageInModal(inputId) {
                const fileInput = document.getElementById(inputId);
                const profileModal = document.getElementById("profile_image_modal");

                if (fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        profileModal.src = e.target.result;
                    };

                    reader.readAsDataURL(fileInput.files[0]);
                }

                // Submit both forms
                const mainForm = document.getElementById("mainForm");
                const imageForm = document.getElementById("imageForm");

                const formData = new FormData(mainForm);
                formData.append("landlord_image", fileInput.files[0]);

                fetch(mainForm.action, {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json()) // You can return JSON response from the server if needed
                .then(data => {
                    // Handle the response if needed
                })
                .catch(error => {
                    console.error("Error:", error);
                });

                // Prevent the default form submission behavior
                return false;
            }

            // Function to close the success message when the close button is clicked
            function closeSuccessMessage() {
                var successMessage = document.querySelector('.success-message');
                successMessage.style.display = 'none';
            }

            function closeErrorMessage() {
                var successMessage = document.querySelector('.error-message');
                successMessage.style.display = 'none';
            }

            // Function to show the password change modal
            function showPasswordModal() {
                document.getElementById("passwordModal").style.display = "flex";
                hideEditModal();
            }

            // Function to hide the password change modal
            function hidePasswordModal() {
                document.getElementById("passwordModal").style.display = "none";
            }

            // Function to show the password change modal
            function showMenuModal() {
                document.getElementById("menuModal").style.display = "flex";
                hidemenuModal();
            }

            // Function to hide the password change modal
            function hideMenuModal() {
                document.getElementById("menuModal").style.display = "none";
            }

            function myFunction(x) {
                x.classList.toggle("change");
            }

            function toggleMenu() {
                event.preventDefault();
                var menu = document.getElementById("menu");
                if (menu.style.display === "block") {
                    menu.style.display = "none";
                } else {
                    menu.style.display = "block";
                }
            }

            function showPassword() {
                var passwordFields = ["old_password", "confirm_password", "new_password"];

                passwordFields.forEach(function(fieldId) {
                    var field = document.getElementById(fieldId);
                    if (field.type === "password") {
                        field.type = "text";
                    } else {
                        field.type = "password";
                    }
                });
            }
        </script>
    </head>
    <body>
        <nav>
            <div class="logo">BH for HOME</div>
        </nav>
        <div class="alert-messages">
            <?php
                // Check if there is a success message in the session and display it
                if (isset($_SESSION['upload_success'])) {
                    echo '<div class="success-message">
                            <span class="close-btn" onclick="closeSuccessMessage()">&times;</span>'
                            . $_SESSION['upload_success'] .
                        '</div>';
                    unset($_SESSION['upload_success']);
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
            
                // Display success message for password change
                if (isset($_SESSION['password_change_success'])) {
                    echo '<div class="success-message">
                            <span class="close-btn" onclick="closeSuccessMessage()">&times;</span>'
                        . $_SESSION['password_change_success'] .
                        '</div>';
                    unset($_SESSION['password_change_success']); 
                } 
            ?>
        </div>
        

        <div class="main">
            <div class="sidebar">
                <div class="owner-profile">
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
                

                <button class="sidebar-btn" style="border-radius: 0 50% 0 0;" onclick="location.href='pg_bh.php'"><i class="fas fa-home"></i> Boarding House</button>
                <button class="sidebar-btn" onclick="location.href='pg_mytenant.php'"><i class="fas fa-users"></i> Tenants</button>
                <div class="space">.</div>
                <a href="?logout=1">Logout</a>
            </div>

            <div class="main-content">
                <div class="owner-name">
                    <div class="profile-con">
                        <div class="profile">
                            <?php if (!empty($landlordInfo['landlord_image'])) : ?>
                                <img id="profile_image" src="<?php echo $landlordInfo['landlord_image']; ?>" alt="profile">
                            <?php else : ?>
                                <img id="profile_image" src="../images/noimage.jfif" alt="profile">
                            <?php endif; ?>
                        </div>
                        <i class="fas fa-edit edit-icon" onclick="showProfileModal()"></i>
                    </div>
                    
                    <div class="name">
                        <?php if ($isAccountSetup) : ?>
                            <h1><?php echo $landlordInfo['firstname'] . ' ' . $landlordInfo['middlename'] . ' ' . $landlordInfo['lastname']; ?></h1>
                        <?php else : ?>
                            <h1>NONE</h1>
                        <?php endif; ?>
                        
                        <p>OWNER</p>
                    </div>

    
                    <div class="menu-icon">
                        <a href="#" onclick="toggleMenu(event)">
                            <div class="container" onclick="myFunction(this)">
                                <div class="bar1"></div>
                                <div class="bar2"></div>
                                <div class="bar3"></div>
                            </div>
                        </a>
                    </div>
                    <div id="menu" class="menu" style="display: none;">
                        <div class="menu-content">
                            <div>
                                <a href="javascript:void(0);" onclick="showEditModal()">Edit About</a>
                            </div>
                            <div>
                                <a href="javascript:void(0);" onclick="showPasswordModal()">Change Password</a>
                            </div>
                        </div>
                    </div>
                </div>
                <h2>ABOUT</h2>
                <div class="about">
                    <div class="label">
                        <p>Username</p>
                        <p>ID</p>
                        <p>Gender</p>
                        <p>Email</p>
                        <p>Address</p>   
                        <p>Contact Number</p> 
                        <p>Birthdate</p>
                    </div>
                    <div class="info">
                        <?php if ($isAccountSetup) : ?>
                            <p><?php echo $username; ?></p>
                            <p><?php echo $landlordInfo['landlord_id']; ?></p>
                            <p style="text-transform: capitalize;"><?php echo $landlordInfo['gender']; ?></p>
                            <p><?php echo $email; ?></p>
                            <p><?php echo $landlordInfo['address']; ?></p>
                            <p><?php echo $landlordInfo['contact_no']; ?></p>
                            <p><?php echo date('F d, Y', strtotime($landlordInfo['birthdate'])); ?></p>
                        <?php else : ?>
                            <p>none</p>
                            <p>none</p>
                            <p>none</p>
                            <p>none</p>
                            <p>none</p>
                            <p>none</p>
                            <p>none</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- WELCOME -->
            <?php if (!$isAccountSetup) : ?>
                <div class="welcomemodal" id="setupAccountModal">
                    <div class="welcomemodal-content">
                        <img src="../images/welcome.gif" alt="Welcome Landlord">
                        <h2>Welcome <?php echo $username; ?></h2>
                        <p>Please set up your account to continue.</p>
                    
                        <button class="btnsure" onclick="showAccountSetupForm()">Sure!</button>
                    </div>
                </div>

                <!-- Set Up Account -->
                <div class="welcomemodal" id="accountSetupForm" style="display: none;">
                    <div class="welcomemodal-content">
                        <di class="close">
                            <button class="close-btn" type="button" onclick="hideAccountSetupForm()">&times;</button>
                        </di>
                        
                        <form action="setup_LRabout.php" method="post" enctype="multipart/form-data">
                            <div class="con1">
                                <div class="textbox" style="display: flex; align-items: center; flex-direction: column;">
                                    <div class="profile">
                                        <img id="welcome_profile_image" src="../images/noimage.jfif" alt="profile">
                                    </div>
                                    <label for="image_input" class="btn-change-image">CHANGE</label>
                                    <input type="file" id="image_input" name="landlord_image" onchange="previewImage()">
                                </div>

                                <div class="name">
                                    <div class="first-last">
                                        <div class="textbox">
                                            <label for="first name">First Name</label>
                                            <input type="text" placeholder="Enter First Name" id="firstname" name="firstname" required>
                                        </div>
                                        <div class="textbox">
                                            <label for="last name">Last Name</label>
                                            <input type="text" placeholder="Enter Last Name" id="lastname" name="lastname" required>
                                        </div>
                                    </div>
                                    <div class="mid">
                                        <div class="textbox">
                                            <label for="middle name">Middle Name</label>
                                            <input type="text" placeholder="Enter Middle Name" id="middlename" name="middlename">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr style="margin-top: 2%;">

                            <div class="textbox">
                                    <label for="address">Address</label>
                                    <input type="text" placeholder="Street, Purok, Barangay, Municipality, Province" style="width: 100%;" id="address" name="address" required>
                                </div>
                                
                            <hr>
                            <div class="contact">
                                <div class="textbox">
                                    <label for="contact number">Contact Number</label>
                                    <input type="text" placeholder="Enter Contact Number" id="contact_no" name="contact_no" required>
                                </div>
                            </div>
                            <hr>
                            <div class="threerow">
                                <div class="textbox">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" required>
                                        <option value="" disabled selected>Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div class="textbox">
                                    <label for="birthdate">Birthdate:</label>
                                    <input style="font-size: 130%;" type="date" id="birthdate" name="birthdate" required>
                                </div>
                            </div>

                            <div class="button-con">
                                <button type="submit">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Edit Information -->
            <div class="edit-modal" id="editModal" style="display: none;">
                <div class="edit-modal-content" style="width: 50%;">
                    <h2>Edit Landlord Information</h2>
                    <form action="pg_landlord.php" method="post" id="mainForm">
                        <div class="textbox">
                            <label for="firstname">First Name</label>
                            <input type="text" placeholder="Enter First Name" id="firstname" name="firstname" value="<?php echo $landlordInfo['firstname']; ?>" required>
                        </div>
                        <div class="textbox" style="display: flex;">
                            <label for="middlename">Middle Name</label>
                            <input type="text" placeholder="Enter Middle Name" id="middlename" name="middlename" value="<?php echo $landlordInfo['middlename']; ?>">
                        </div>
                        <div class="textbox">
                            <label for="lastname">Last Name</label>
                            <input type="text" placeholder="Enter Last Name" id="lastname" name="lastname" value="<?php echo $landlordInfo['lastname']; ?>" required>
                        </div>
                        <div class="textbox" style="display: flex;">
                            <label for="contact_no">Contact Number</label>
                            <input type="text" placeholder="Enter Contact Number" id="contact_no" name="contact_no" value="<?php echo $landlordInfo['contact_no']; ?>" required>
                        </div>
                        <div class="textbox">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" required>
                                <option value="" disabled>Select Gender</option>
                                <option value="male" <?php if ($landlordInfo['gender'] === 'male') echo 'selected'; ?>>Male</option>
                                <option value="female" <?php if ($landlordInfo['gender'] === 'female') echo 'selected'; ?>>Female</option>
                                <option value="other" <?php if ($landlordInfo['gender'] === 'other') echo 'selected'; ?>>Other</option>
                            </select>
                        </div>
                        <div class="textbox" style="display: flex;">
                            <label for="address">Address</label>
                            <input type="text" placeholder="Street, Purok, Barangay, Municipality, Province" style="width: 100%;" id="address" name="address" value="<?php echo $landlordInfo['address']; ?>" required>
                        </div>
                        <div class="textbox">
                            <label for="birthdate">Birthdate:</label>
                            <input type="date" id="birthdate" name="birthdate" value="<?php echo $landlordInfo['birthdate']; ?>" required>
                        </div>
                        <div class="button-con">
                            <button type="submit" name="change_info">Save Changes</button>
                            <button type="button" onclick="hideEditModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Profile Image Modal -->
            <div class="profile-modal" id="profileModal" style="display: none;">
                <div class="profile-modal-content">
                    <h3>Profile Photo</h3>
                    <form action="pg_landlord.php" method="post" enctype="multipart/form-data" id="imageForm">
                        <div class="textbox" style="display: flex; align-items: center; flex-direction: column;">
                            <input type="hidden" name="firstname" value="<?php echo $landlordInfo['firstname']; ?>">
                            <input type="hidden" name="middlename" value="<?php echo $landlordInfo['middlename']; ?>">
                            <input type="hidden" name="lastname" value="<?php echo $landlordInfo['lastname']; ?>">
                            <input type="hidden" name="contact_no" value="<?php echo $landlordInfo['contact_no']; ?>">
                            <input type="hidden" name="gender" value="<?php echo $landlordInfo['gender']; ?>">
                            <input type="hidden" name="address" value="<?php echo $landlordInfo['address']; ?>">
                            <input type="hidden" name="birthdate" value="<?php echo $landlordInfo['birthdate']; ?>">
            
                            <div class="profile">
                                <img id="profile_image_modal" src="" alt="profile">
                            </div>
                            <label for="image_input_modal" class="btn-change-image">CHANGE</label>
                            <input type="file" id="image_input_modal" name="landlord_image" onchange="previewImageInModal('image_input_modal')" style="display: none;">
                        </div>
                                
                        <div class="button-con">
                            <button type="submit" name="change_info">Save</button>
                            <button type="button" onclick="hideProfileModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password Change Modal -->
            <div class="password-modal" id="passwordModal" style="display: none;">
                <div class="password-modal-content">
                    <h3>Change Password</h3>
                    <form action="pg_landlord.php" method="post">
                        <div class="textbox">
                            <label for="old_password">Current Password</label>
                            <input type="password" placeholder="Enter Current Password" id="old_password" name="old_password" required>
                        </div>
                        <div class="textbox">
                            <label for="new_password">New Password</label>
                            <input type="password" placeholder="Enter New Password" id="new_password" name="new_password" required>
                        </div>
                        <div class="textbox">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" placeholder="Confirm New Password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <input type="checkbox" onclick="showPassword()"> Show Password
                        <div class="button-con">
                            <button type="submit" name="change_password">Change Password</button>
                            <button type="button" onclick="hidePasswordModal()">Cancel</button>
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