<?php
     session_start();
     @include '../conn/config.php';
     $errors = array();
 
     // Check if the user is logged in (you may want to add additional checks for security)
     if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'tenant') {
         // Retrieve user information from session variables
         $username = $_SESSION['username'];
         $userType = $_SESSION['user_type'];
         $email = $_SESSION['email'];
         $tenant_id = $_SESSION['user_id'];
 
         // Check if the tenant_id exists in the "tenant about" table
         $check_query = "SELECT * FROM tenant_about WHERE tenant_id = $tenant_id";
         $check_result = mysqli_query($conn, $check_query);
 
         if (mysqli_num_rows($check_result) == 0) {
             $setup_message = "Please set up your account.";
         } else {
             $tenant_data = mysqli_fetch_assoc($check_result);
         }
     } else { 
         echo "You are not authorized to access this page.";
         header("Location: ../Registration/frm_login.php");
         exit();
     }
 
     // Check if the user clicked the "Logout" link
     if (isset($_GET['logout'])) {
         // Destroy the session to log the user out
         session_destroy();
 
         // Redirect the user back to the login page
         header("Location: ../Registration/frm_login.php");
         exit();
     }

    // Check if the form is submitted
    if (isset($_POST['change_img'])) {
        // Check if a file is selected
        if (isset($_FILES['tenant_image']) && $_FILES['tenant_image']['error'] === 0) {
            $target_dir = "TenantProfiles/";
            $target_file = $target_dir . basename($_FILES["tenant_image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if the file is an image
            $check = getimagesize($_FILES["tenant_image"]["tmp_name"]);
            if ($check !== false) {
                // Allow certain file formats
                $allowed_types = array("jpg", "jpeg", "png", "gif");
                if (in_array($imageFileType, $allowed_types)) {
                    // Move the file to the target directory
                    if (move_uploaded_file($_FILES["tenant_image"]["tmp_name"], $target_file)) {
                        // Update the tenant image path in the database
                        $update_query = "UPDATE tenant_about SET tenant_image = '$target_file' WHERE tenant_id = $tenant_id";
                        mysqli_query($conn, $update_query);
                        
                        $_SESSION['upload_success'] = "Image changed successfully.";
                        header("Location: tenant_myprofile.php");
                        exit();
                    } else {
                        $errors[] = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $errors[] = "Sorry, only JPG, JPEG, PNG, and GIF files are allowed.";
                }
            } else {
                $errors[] = "File is not an image.";
            }
        } else {
            $errors[] = "No file selected.";
        }
    }

    // Check if the form is submitted
    if (isset($_POST['change_info'])) {
        // Sanitize and validate the input data
        $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
        $middlename = mysqli_real_escape_string($conn, $_POST['middlename']);
        $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
        $contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']);
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $birthdate = mysqli_real_escape_string($conn, $_POST['birthdate']);
        $occupation = mysqli_real_escape_string($conn, $_POST['occupation']);
        $parents_contact = mysqli_real_escape_string($conn, $_POST['parents_contact']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);

        // Update the tenant information in the database
        $update_query = "UPDATE tenant_about SET 
            firstname = '$firstname',
            middlename = '$middlename',
            lastname = '$lastname',
            contact_no = '$contact_no',
            gender = '$gender',
            birthdate = '$birthdate',
            occupation = '$occupation',
            parents_contact = '$parents_contact',
            address = '$address'
            WHERE tenant_id = $tenant_id";

        $update_result = mysqli_query($conn, $update_query);

        if ($update_result) {
            $_SESSION['upload_success'] = "Profile updated successfully.";
            header("Location: tenant_myprofile.php");
            exit();
        } else {
            // Update failed
            $errors[] = "Failed to update tenant information. Please try again.";
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
                header("Location: tenant_myprofile.php");
                exit();
            } else {
                $errors[] = "Failed to change password. Please try again later.";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>My Profile</title>
        <link rel="stylesheet" type="text/css" href="../Style/style_tenant-profile.css"> 
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <script>
            // Menu
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

            //Edit Profile Image

            function showEditProfile() {
                var modal = document.getElementById('editProfile');
                modal.style.display = 'flex';
            }

            function hideEditProfile() {
                var modal = document.getElementById('editProfile');
                modal.style.display = 'none';
            }

            document.getElementById("image_input").addEventListener("change", function () {
                previewImage('image_input');
            });

            function previewImage(inputId) {
                const fileInput = document.getElementById(inputId);
                const profileModal = document.getElementById("prev_profile_image");

                if (fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        profileModal.src = e.target.result;
                    };

                    reader.readAsDataURL(fileInput.files[0]);
                }
            }

            // Edit About
            function showEditInfo() {
                document.getElementById("editInfo").style.display = "flex";
            }

            function hideEditInfo() {
                document.getElementById("editInfo").style.display = "none";
            }

            // Password
             function showPasswordModal() {
                document.getElementById("passwordModal").style.display = "flex";
                hideEditModal();
            }

            function hidePasswordModal() {
                document.getElementById("passwordModal").style.display = "none";
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

            // Alert Messages
            function closeSuccessMessage() {
                var successMessage = document.querySelector('.success-message');
                successMessage.style.display = 'none';
            }

            function closeErrorMessage() {
                var successMessage = document.querySelector('.error-message');
                successMessage.style.display = 'none';
            }
        </script>
    </head>
    <body>
        <nav>
            <div class="logo" style="flex: 1;">BH for HOME</div>
            <div style="flex: 1;">
                <button style="cursor: pointer;" onclick="location.href='pg_tenant.php';"><i class="fa fa-home"></i></button>
            </div>
            <div class="textbox" style="display: flex; align-items: center; flex-direction: column;">
                <div class="profile" onclick="location.href='tenant_myprofile.php';" style="cursor: pointer;">
                    <?php if (!empty($tenant_data['tenant_image'])): ?>
                        <img  id="profile_image" src="<?php echo $tenant_data['tenant_image']; ?>" alt="profile">
                    <?php else: ?>
                        <img  id="profile_image" src="../images/noimage.jfif" alt="profile">
                    <?php endif; ?>
                </div>             
            </div>
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
            <?php if (isset($setup_message)): ?>
                <p class="intro-msg">Hello <span style="font-weight: bold;"><?php echo $username; ?></span>, Welcome to BH for Home. <br>
                <span class="setup-msg">Please set up your account to start browsing for your new home.</span> <br>
                <button onclick="location.href='pg_tenant.php';" class="home-btn">HOME</button>
            <?php else: ?>
                <div class="main-content">
                    <div class="owner-name">
                        <div class="profile-con">
                            <div class="profile">
                                <?php if (!empty($tenant_data['tenant_image'])) : ?>
                                    <img id="profile_image" src="<?php echo $tenant_data['tenant_image']; ?>" alt="profile">
                                <?php else : ?>
                                    <img id="profile_image" src="../images/noimage.jfif" alt="profile">
                                <?php endif; ?>
                            </div>
                            <i class="fas fa-edit edit-icon" onclick="showEditProfile()"></i>
                        </div>

                        <div class="name">
                            <h1><?php echo $tenant_data['firstname'] . ' ' . $tenant_data['middlename'] . ' ' . $tenant_data['lastname']; ?></h1>
                            <p>TENANT</p>
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
                                    <a href="javascript:void(0);" onclick="showEditInfo()">Edit About</a>
                                </div>
                                <div>
                                    <a href="javascript:void(0);" onclick="showPasswordModal()">Change Password</a>
                                </div>
                                <div>
                                    <a href="?logout=1">Logout</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <h2>ABOUT</h2>
                    <div class="about">
                        <div class="label">
                            <p>Tenant ID:</p>
                            <p>Birthdate:</p>
                            <p>Gender:</p>
                            <p>Address:</p>
                            <p>Occupation:</p>
                            <p>Contact Number:</p>
                            <p>Parents Contact:</p>
                        </div>
                        <div class="info">
                                <p><?php echo $tenant_data['tenant_id']; ?></p>
                                <p><?php echo date('F d, Y', strtotime($tenant_data['birthdate'])); ?></p>
                                <p style="text-transform: capitalize;"><?php echo $tenant_data['gender']; ?></p>
                                <p><?php echo $tenant_data['address']; ?></p>
                                <p><?php echo $tenant_data['occupation']; ?></p>
                                <p><?php echo $tenant_data['contact_no']; ?></p>
                                <p><?php echo $tenant_data['parents_contact']; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Edit Profile Image -->
        <div id="editProfile" class="edit-profile">
            <div class="edit-profile-content">
                <div style="display: flex;">
                    <h3 style="flex: 1;">Profile Photo</h3>
                    <span class="close-edit-profile" onclick="hideEditProfile()">&times;</span>
                </div>
                
                <form action="tenant_myprofile.php" method="post" enctype="multipart/form-data" id="imageForm">
                    <div class="textbox" style="display: flex; align-items: center; flex-direction: column;">
                        <input type="hidden" name="firstname" value="<?php echo $tenant_data['firstname']; ?>">
                        <input type="hidden" name="middlename" value="<?php echo $tenant_data['middlename']; ?>">
                        <input type="hidden" name="lastname" value="<?php echo $tenant_data['lastname']; ?>">
                        <input type="hidden" name="birthdate" value="<?php echo $tenant_data['birthdate']; ?>">
                        <input type="hidden" name="gender" value="<?php echo $tenant_data['gender']; ?>">
                        <input type="hidden" name="address" value="<?php echo $tenant_data['address']; ?>">
                        <input type="hidden" name="occupation" value="<?php echo $tenant_data['occupation']; ?>">
                        <input type="hidden" name="contact_no" value="<?php echo $tenant_data['contact_no']; ?>">
                        <input type="hidden" name="parents_contact" value="<?php echo $tenant_data['parents_contact']; ?>">
                
                        <div class="profile">
                            <img id="prev_profile_image" src="<?php echo $tenant_data['tenant_image']; ?>" alt="profile">
                        </div>
                        <label for="image_input" class="btn-change-image">CHANGE</label>
                        <input type="file" id="image_input" name="tenant_image" onchange="previewImage('image_input')" style="display: none;">
                    </div>

                    <div class="button-con">
                        <button class="profile-save" type="submit" name="change_img">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Information -->
        <div class="edit-info" id="editInfo" style="display: none;">
            <div class="edit-info-content" style="width: 50%;">
                    <div style="display: flex;">
                        <h3 style="flex: 1;">Edit Your Profile</h3>
                        <span class="close-edit-info" onclick="hideEditInfo()">&times;</span>
                    </div>

                    <hr>
                    
                    <form action="tenant_myprofile.php" method="post" id="mainForm">

                        <div class="textbox">
                            <label for="firstname">First Name:</label>
                            <input type="text" placeholder="Enter First Name" id="firstname" name="firstname" value="<?php echo $tenant_data['firstname']; ?>" required>
                        </div>

                        <div class="textbox">
                            <label for="middlename">Middle Name:</label>
                            <input type="text" placeholder="Enter Middle Name" id="middlename" name="middlename" value="<?php echo $tenant_data['middlename']; ?>">
                        </div>

                        <div class="textbox">
                            <label for="lastname">Last Name:</label>
                            <input type="text" placeholder="Enter Last Name" id="lastname" name="lastname" value="<?php echo $tenant_data['lastname']; ?>" required>
                        </div>

                        <div class="textbox">
                            <label for="contact_no">Contact Number:</label>
                            <input type="text" placeholder="Enter Contact Number" id="contact_no" name="contact_no" value="<?php echo $tenant_data['contact_no']; ?>" required>
                        </div>

                        <div class="textbox">
                            <label for="gender">Gender:</label>
                            <select id="gender" name="gender" required>
                                <option value="" disabled>Select Gender</option>
                                <option value="male" <?php if ($tenant_data['gender'] === 'male') echo 'selected'; ?>>Male</option>
                                <option value="female" <?php if ($tenant_data['gender'] === 'female') echo 'selected'; ?>>Female</option>
                                <option value="other" <?php if ($tenant_data['gender'] === 'other') echo 'selected'; ?>>Other</option>
                            </select>
                        </div>

                        <div class="textbox">
                            <label for="birthdate">Birthdate:</label>
                            <input type="date" id="birthdate" name="birthdate" value="<?php echo $tenant_data['birthdate']; ?>" required>
                        </div>
                        
                        <div class="textbox">
                            <label for="occupation">Occupation:</label>
                            <input type="text" placeholder="Enter Occupation" id="occupation" name="occupation" value="<?php echo $tenant_data['occupation']; ?>" required>
                        </div>

                        <div class="textbox">
                            <label for="parents_contact">Parents Contact:</label>
                            <input type="text" placeholder="Enter Parents Contact" id="parents_contact" name="parents_contact" value="<?php echo $tenant_data['parents_contact']; ?>" required>
                        </div>

                        <div class="textbox">
                            <label for="address">Address:</label>
                            <input style="width: 20rem;" type="text" placeholder="Street, Purok, Barangay, Municipality, Province" id="address" name="address" value="<?php echo $tenant_data['address']; ?>" required>
                        </div>

                        <div class="button-con">
                            <button type="submit" name="change_info">Save Changes</button>
                        </div>
                    </form>
            </div>
        </div>

        <!-- Password Change Modal -->
        <div class="password-modal" id="passwordModal" style="display: none;">
                <div class="password-modal-content">
                    <div style="display: flex;">
                        <h3 style="flex: 1;">Edit Your Profile</h3>
                        <span class="close-changepass" onclick="hidePasswordModal()">&times;</span>
                    </div>
                    <hr>
                    <form action="tenant_myprofile.php" method="post">
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
                        </div>
                    </form>
                </div>
        </div>
        
        <footer class="footer">
            &copy; 2023 Boarding House Booking. All rights reserved.
        </footer>
    </body>
</html>