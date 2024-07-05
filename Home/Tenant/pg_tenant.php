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

    // Handle form submission for setting up the tenant's account
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_tenant'])) {
        // Sanitize and validate form data here
        // Check if the form fields are set before accessing them
        $firstname = isset($_POST['firstname']) ? mysqli_real_escape_string($conn, $_POST['firstname']) : '';
        $middlename = isset($_POST['middlename']) ? mysqli_real_escape_string($conn, $_POST['middlename']) : '';
        $lastname = isset($_POST['lastname']) ? mysqli_real_escape_string($conn, $_POST['lastname']) : '';
        $birthdate = isset($_POST['birthdate']) ? mysqli_real_escape_string($conn, $_POST['birthdate']) : '';
        $gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, $_POST['gender']) : '';
        $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : '';
        $occupation = isset($_POST['occupation']) ? mysqli_real_escape_string($conn, $_POST['occupation']) : '';
        $contact_no = isset($_POST['contact_no']) ? mysqli_real_escape_string($conn, $_POST['contact_no']) : '';
        $parents_contact = isset($_POST['parents_contact']) ? mysqli_real_escape_string($conn, $_POST['parents_contact']) : '';
        
        // Handle image upload
        if (isset($_FILES['tenant_image']) && $_FILES['tenant_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = "TenantProfiles/"; // Replace with your image upload directory
            $tenantImageFileName = basename($_FILES['tenant_image']['name']);
            $tenantImageFilePath = $uploadDir . $tenantImageFileName;
            move_uploaded_file($_FILES['tenant_image']['tmp_name'], $tenantImageFilePath);
        } else {
            $tenantImageFilePath = ""; // If no new image uploaded
        }

        // Insert data into the "tenant_about" table
        $insert_query = "INSERT INTO tenant_about (tenant_id, firstname, middlename, lastname, birthdate, gender, address, occupation, contact_no, parents_contact, tenant_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "issssssssss", $tenant_id, $firstname, $middlename, $lastname, $birthdate, $gender, $address, $occupation, $contact_no, $parents_contact, $tenantImageFilePath);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Redirect or display a success message
        header("Location: pg_tenant.php"); // Redirect to tenant's dashboard or any other appropriate page
        exit();
    }

    // Fetch data from the bh_info table
    $bhInfoQuery = "SELECT * FROM bh_info";
    $bhInfoResult = mysqli_query($conn, $bhInfoQuery);

    // Fetch data from the owner_about table
    $ownerAboutQuery = "SELECT * FROM owner_about";
    $ownerAboutResult = mysqli_query($conn, $ownerAboutQuery);

    $select_query = "SELECT * FROM tenant_about WHERE tenant_id = $tenant_id";
    $result = mysqli_query($conn, $select_query);

    if ($result && mysqli_num_rows($result) > 0) {
        $tenant_data = mysqli_fetch_assoc($result);
    }

    // Check if the tenant_id exists in the "tenant_about" table
    $check_query = "SELECT * FROM tenant_about WHERE tenant_id = $tenant_id";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) == 0) {
        $tenant_data = mysqli_fetch_assoc($check_result);
    } elseif (mysqli_num_rows($check_result) > 0) {
        $tenant_data = mysqli_fetch_assoc($check_result);
    
        if ($tenant_data['bh_id'] !== null) {
            // Retrieve the bh_id associated with the tenant
            $bh_id = $tenant_data['bh_id'];
            $room_id = $tenant_data['room_id'];
        
            // Fetch data from the bh_info table for the corresponding bh_id
            $bhInfoQuery = "SELECT * FROM bh_info WHERE bh_id = $bh_id";
            $bhInfoResult = mysqli_query($conn, $bhInfoQuery);
            $bhInfoData = mysqli_fetch_assoc($bhInfoResult);

            $landlord_id = $bhInfoData['landlord_id']; 

            // Fetch data from the room_info table for the corresponding room_id
            $roomInfoQuery = "SELECT * FROM room_info WHERE room_id = $room_id";
            $roomInfoResult = mysqli_query($conn, $roomInfoQuery);
            $roomInfoData = mysqli_fetch_assoc($roomInfoResult);

            // Fetch data from the owner_about table for the corresponding landlord_id
            $ownerInfoQuery = "SELECT * FROM owner_about WHERE landlord_id = $landlord_id";
            $ownerInfoResult = mysqli_query($conn, $ownerInfoQuery);
            $ownerInfoData = mysqli_fetch_assoc($ownerInfoResult);

            $bhInfoResult = mysqli_query($conn, $bhInfoQuery);
        }
    }

    // Handle confirmation form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['confirm_cancel'])) {
        // Update bh_id, room_id, and status to NULL
        $update_query = "UPDATE tenant_about SET bh_id = NULL, room_id = NULL, status = NULL WHERE tenant_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $tenant_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        // Redirect or display a success message
        header("Location: pg_tenant.php");
        exit();
    }

     // Check if the request is a POST request
     if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['tenant_id'])) {
        // Get the tenant_id from the POST data
        $tenant_id = $_POST['tenant_id'];

        // Update bh_id, room_id, and status to NULL
        $update_query = "UPDATE tenant_about SET bh_id = NULL, room_id = NULL, status = NULL WHERE tenant_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $tenant_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        // You can send a response if needed
        echo "Tenant data updated successfully.";
    }

    
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Tenant Page</title>
        <link rel="stylesheet" type="text/css" href="../Style/style_tenant_pg.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script>
            function showSetupForm() {
                const setupForm = document.getElementById('SetupTenantForm');
                setupForm.style.display = 'block';
            }

            // Function to preview and update the selected image
            function previewImage() {
                var input = document.getElementById("image_input");
                var preview = document.getElementById("welcome_profile_image");

                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        preview.src = e.target.result;
                    };

                    reader.readAsDataURL(input.files[0]);
                }
            }

            // Function to hide the account setup form
            function hideAccountSetupForm() {
                document.getElementById("SetupTenantForm").style.display = "none";
            }

            function storeBhIdAndRedirect(bhId) {
                window.location.href = 'view_bh.php?bh_id=' + bhId;
            }

            //toggle for owner and bh info

            function togglebhinfo() {
                var bhinfoContent = document.getElementById("bhinfoContent");
                var header = document.getElementById("bhinfoHeader");
                var bharrowIcon = document.querySelector(".arrow");
                var bhInfo = document.querySelector(".bh-info-tgl");

                if (bhinfoContent.style.display === "block") {
                    bhinfoContent.style.display = "none";
                    bharrowIcon.classList.remove("bhup");
                    bharrowIcon.classList.add("bhdown");
                    bhInfo.classList.remove("bh-info-open");
                } else {
                    bhinfoContent.style.display = "block";
                    bharrowIcon.classList.remove("bhdown");
                    bharrowIcon.classList.add("bhup");
                    bhInfo.classList.add("bh-info-open");
                }
            }

            function toggleownerinfo() {
                var ownerinfoContent = document.getElementById("ownerinfoContent");
                var header = document.getElementById("ownerinfoHeader");
                var ownerarrowIcon = document.querySelector(".ownerarrow");
                var ownerInfo = document.querySelector(".owner-info-tgl");

                if (ownerinfoContent.style.display === "block") {
                    ownerinfoContent.style.display = "none";
                    ownerarrowIcon.classList.remove("ownerup");
                    ownerarrowIcon.classList.add("ownerdown");
                    ownerInfo.classList.remove("owner-info-open");
                } else {
                    ownerinfoContent.style.display = "block";
                    ownerarrowIcon.classList.remove("ownerdown");
                    ownerarrowIcon.classList.add("ownerup");
                    ownerInfo.classList.add("owner-info-open");
                }
            }

            //Confirm Cancel
            function openConfirmCancelation() {
                document.getElementById('confirmCancelation').style.display = 'flex';
            }

            function closeConfirmCancelation() {
                document.getElementById('confirmCancelation').style.display = 'none';
            }

            //GMAP
            function openGMapModal() {
                var gmapModal = document.getElementById('gmap');
                gmapModal.style.display = 'block';

                // Add an event listener to close the modal when clicking outside the content
                window.addEventListener('click', function(event) {
                    if (event.target === gmapModal) {
                        closeGMapModal();
                    }
                });
            }

            function closeGMapModal() {
                var gmapModal = document.getElementById('gmap');
                gmapModal.style.display = 'none';
            }

            //Licence
            function openLicense() {
                var licenseModal = document.getElementById('license');
                licenseModal.style.display = 'block';

                // Add an event listener to close the modal when clicking outside the content
                window.addEventListener('click', function (event) {
                    if (event.target === licenseModal) {
                        closeLicense();
                    }
                });
            }

            function closeLicense() {
                var license = document.getElementById('license');
                license.style.display = 'none';
            }

            function updateTenantData() {
                // Send an AJAX request to a PHP script to update the database
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'pg_tenant.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        // Handle the response if needed
                        var response = xhr.responseText;
                        console.log(response); // You can remove this line or use it for debugging
                        // Redirect or perform any other actions as needed
                        window.location.href = 'pg_tenant.php';
                    }
                };

                // Send the tenant_id to the PHP script
                var tenantId = <?php echo $tenant_id; ?>;
                xhr.send('tenant_id=' + tenantId);
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
       
        <div class="main">
            <?php if (mysqli_num_rows($check_result) == 0): ?>
                <p class="intro-msg">Hello <span style="font-weight: bold;"><?php echo $username; ?></span>, Welcome to BH for Home. <br>
                <span class="setup-msg">Please set up your account to start browsing for your new home.</span> <br>
                <button class="setup-btn" onclick="showSetupForm()">Set Up Account</button><br>
                <a href="?logout=1" class="btn-logout">Logout</a></p>
            <?php elseif (mysqli_num_rows($check_result) > 0 && $tenant_data['bh_id'] == null): ?>
                <div class="card-container">
                    <?php while ($bhInfoData = mysqli_fetch_assoc($bhInfoResult)) {
                        //storing the bh_id
                        $bh_id = $bhInfoData['bh_id'];
                        $_SESSION['bh_id'][] = $bh_id;

                        // Fetch owner data based on the landlord_id from bh_info
                        $landlord_id = $bhInfoData['landlord_id'];
                        $ownerAboutQuery = "SELECT * FROM owner_about WHERE landlord_id = $landlord_id";
                        $ownerAboutResult = mysqli_query($conn, $ownerAboutQuery);

                        if ($ownerAboutResult && mysqli_num_rows($ownerAboutResult) > 0) {
                            $ownerAboutData = mysqli_fetch_assoc($ownerAboutResult);
                        } 
                    ?>
                        <div class="card">
                           <div class="bh-img-con">
                                <img id="bh-img" src="../Landlord/<?php echo $bhInfoData['bh_img']; ?>" alt="Boarding House Image">
                            </div>
                            <div class="bh-info">
                                <div class="header">
                                    <h1><?php echo $bhInfoData['business_name']; ?></h1>
                                    <h3><?php echo $ownerAboutData['firstname'] . ' ' . $ownerAboutData['lastname']; ?></h3>
                                    <p>owner</p>
                                </div>
                                <hr>
                                <div class="details" style="margin-top: 0.5rem;">
                                    <div style="display: flex; justify-content: space-between;">
                                        <div class="label">
                                            <p>Location:</p>
                                            <p>Landmark:</p>
                                            <p>Number of Rooms:</p>
                                        </div>
                                        <div class="data" style="font-weight: bold;">
                                            <p><?php echo $bhInfoData['location']; ?></p>
                                            <p><?php echo $bhInfoData['landmark']; ?></p>
                                            <p><?php echo $bhInfoData['no_rooms']; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="des">
                                        <p><?php echo $bhInfoData['description']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="actions">
                                <button onclick="storeBhIdAndRedirect(<?php echo $bh_id; ?>);">View More</button>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php elseif (mysqli_num_rows($check_result) > 0 && $tenant_data['bh_id'] !== null): ?>
                    <div class="view-payment">
                        <?php if ($tenant_data['status'] == "pending"): ?>
                            <p style="color: red; font-weight: bold;">Your booking is still pending. You can also contact your landlord and wait for landlord's approval.</p>
                            <button class="btn-cancel" onclick="openConfirmCancelation()">Cancel Booking</button>
                        <?php elseif ($tenant_data['status'] == "approved"): ?>
                            <a href="pg_viewpayments.php?tenant_id=<?php echo $tenant_id; ?>">VIEW PAYMENTS</a>
                        <?php elseif ($tenant_data['status'] == "denied" or "kicked"): ?>
                            <p>You have been <?php echo $tenant_data['status']; ?>. Please click okay to start browsing for another Boarding House.</p>
                            <button onclick="updateTenantData()">OKAY</button>
                        <?php endif; ?>
                    </div>
                    <h1 style="text-align: center;">My Boarding House</h1>
                    <div class="my-bh-con" style="display: flex;">
                        <div class="bh-owner">
                            <h1><?php echo $bhInfoData['business_name']; ?></h1>
                            <p style="text-align: center;"><strong>BUSINESS NAME</strong></p>

                            <div class="owner">
                                <img src="../Landlord/<?php echo $ownerInfoData['landlord_image']; ?>" alt="Landlord Image">
                                <div class="owner-name">
                                    <p><?php echo $ownerInfoData['firstname'] . ' ' . $ownerInfoData['middlename'] . ' ' . $ownerInfoData['lastname']; ?></p>
                                    <strong>OWNER</strong>
                                </div>
                            </div>

                            <img class="bh-img" src="../Landlord/<?php echo $bhInfoData['bh_img']; ?>" alt="Boarding House Image">
                            <p class="bh-des"><?php echo $bhInfoData['description']; ?></p>

                            <div class="info">
                                <div class="bh-info-tgl">
                                    <div class="bh-info-header" id="bhinfoHeader" onclick="togglebhinfo()">
                                        <p>Boarding House Information</p>
                                        <div><i class="arrow down"></i></div>
                                    </div>
                                    <div class="bh-info-content" id="bhinfoContent">
                                        <div style="display: flex;">
                                            <div class="label">
                                                <p>BH ID:</p>
                                                <p>Location:</p>
                                                <p>Landmark:</p>
                                                <p>Number of Rooms:</p>
                                                <p>License:</p>
                                                <p>Google Map:</p>
                                            </div>
                                            <div class="data">
                                                <p><strong><?php echo $bhInfoData['bh_id']; ?></strong></p>
                                                <p><strong><?php echo $bhInfoData['location']; ?></strong></p>
                                                <p><strong><?php echo $bhInfoData['landmark']; ?></strong></p>
                                                <p><strong><?php echo $bhInfoData['no_rooms']; ?></strong></p>
                                                <p><button onclick="openLicense()">View License</button></p>
                                                <p><button onclick="openGMapModal()">View Google Map</button></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="owner-info-tgl">
                                    <div class="owner-info-header" id="ownerinfoHeader" onclick="toggleownerinfo()">
                                        <p>Owner Information</p>
                                        <div><i class="ownerarrow down"></i></div>
                                    </div>
                                    <div class="owner-info-content" id="ownerinfoContent">
                                        <div style="display: flex;">
                                            <div class="label">
                                                <p>Landlord ID:</p>
                                                <p>Contact Number:</p>
                                                <p>Gender:</p>
                                                <p>Address:</p>
                                                <p>Birthdate:</p>
                                            </div>
                                            <div class="data">
                                                <p><strong><?php echo $ownerInfoData['landlord_id']; ?></strong></p>
                                                <p><strong><?php echo $ownerInfoData['contact_no']; ?></strong></p>
                                                <p><strong><?php echo $ownerInfoData['gender']; ?></strong></p>
                                                <p><strong><?php echo $ownerInfoData['address']; ?></strong></p>
                                                <p><strong><?php echo date('F j, Y', strtotime($ownerInfoData['birthdate'])); ?></strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="room">
                            <h1>ROOM INFO</h1>
                            <h2>Room #<?php echo $roomInfoData['room_no']; ?> | ₱<?php echo $roomInfoData['price']; ?></h2>
                            <div class="img">
                                <div class="room-image">
                                    <img id="img-1" src="../Landlord/<?php echo $roomInfoData['room_img_1']; ?>" alt="Room Image 1" onclick="showImageGallery(1)">
                                    <img id="img-2" src="../Landlord/<?php echo $roomInfoData['room_img_2']; ?>" alt="Room Image 2" onclick="showImageGallery(2)">
                                    <img id="img-3" src="../Landlord/<?php echo $roomInfoData['room_img_3']; ?>" alt="Room Image 3" onclick="showImageGallery(3)">
                                </div>
                            </div>
                            <div class="room-des"><?php echo $roomInfoData['room_description']; ?></div>
                            <div class="room-data">
                                <div class="label">
                                    <p>Bed:</p>
                                    <p>Light:</p>
                                    <p>Outlet:</p>
                                    <p>Tables:</p>
                                    <p>Chair:</p>
                                    <p>Air Conditioner:</p>
                                    <p>Electric Fan:</p>
                                </div>
                                <div class="data">
                                    <p><strong><?php echo $roomInfoData['bed']; ?></strong></p>
                                    <p><strong><?php echo $roomInfoData['light']; ?></strong></p>
                                    <p><strong><?php echo $roomInfoData['outlet']; ?></strong></p>
                                    <p><strong><?php echo $roomInfoData['tables']; ?></strong></p>
                                    <p><strong><?php echo $roomInfoData['chair']; ?></strong></p>
                                    <p><strong><?php echo $roomInfoData['aircon']; ?></strong></p>
                                    <p><strong><?php echo $roomInfoData['electricfan']; ?></strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php endif; ?>
        </div>

        <!-- Set Up Form -->
        <div class="setuptenant" id="SetupTenantForm" style="display: none;">
            <div class="setuptenant-content">
                <div class="close">
                    <button class="close-btn" type="button" onclick="hideAccountSetupForm()">&times;</button>
                </div>

                <h3>Tenant Profile</h3>

                <hr style="margin-bottom: 2%;">

                <form action="pg_tenant.php" method="post" enctype="multipart/form-data">
                    <div class="row" style="display: flex; justify-content: space-evenly;">
                        <div class="textbox" style="display: flex; align-items: center; flex-direction: column;">
                            <div class="profile">
                                <img id="welcome_profile_image" src="../images/noimage.jfif" alt="profile">
                            </div>
                            <label for="image_input" class="btn-change-image">CHANGE</label>
                            <input type="file" id="image_input" name="tenant_image" onchange="previewImage()">                        
                        </div>

                        <div class="name">
                            <div class="textbox">
                                <label for="firstname">First Name:</label>
                                <input type="text" id="firstname" name="firstname" placeholder="Enter First Name" required><br>
                            </div>

                            <div class="textbox">
                                <label for="lastname">Last Name:</label>
                                <input type="text" id="lastname" name="lastname" placeholder="Enter Last Name" required><br>
                            </div>
                        </div>

                        <div class="textbox">
                            <label for="middlename">Middle Name:</label>
                            <input type="text" id="middlename" name="middlename" placeholder="Enter Middle Name"><br>
                        </div>
                    </div>

                    <hr style="margin-bottom: 2%;">
                    
                    <div class="row" style="display: flex; justify-content: space-evenly;">
                        <div class="textbox">
                            <label for="gender">Gender:</label>
                            <select id="gender" name="gender" required>
                                <option value="" disabled selected>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select><br>
                        </div>

                        <div class="textbox">
                            <label for="birthdate">Birthdate:</label>
                            <input type="date" id="birthdate" name="birthdate" placeholder="Enter Birthdate" required><br>
                        </div>

                        <div class="textbox">
                            <label for="occupation">Occupation:</label>
                            <input type="text" id="occupation" name="occupation" placeholder="Enter Occupation" required><br>
                        </div>
                    </div>

                    <div class="row" style="display: flex;">
                        <div class="textbox">
                            <label for="address">Address:</label>
                            <input type="text" id="address" name="address" placeholder="Enter Address" required><br>
                        </div>

                        
                        <div class="textbox">
                            <label for="contact_no">Contact No:</label>
                            <input type="text" id="contact_no" name="contact_no" placeholder="Enter Contact No" required><br>
                        </div>

                        <div class="textbox">
                            <label for="parents_contact">Parents Contact:</label>
                            <input type="text" id="parents_contact" name="parents_contact" placeholder="Enter Parents Contact"><br>
                        </div>
                    </div>

                    <div class="btn">
                        <button type="submit" name="submit_tenant">Submit</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Confirm Cancelation -->
        <div id="confirmCancelation" class="confirm-cancelation">
            <div class="confirm-cancelation-content">
                <div style="display: flex; flex-direction:column;">
                    <div style="display: flex; justify-content:end; margin-bottom: 1rem;">
                        <span class="close" onclick="closeConfirmCancelation()">&times;</span>
                    </div>
                    <h2>Are you sure you want to cancel Room #<?php echo $roomInfoData['room_no']; ?> in <?php echo $bhInfoData['business_name']; ?>?</h2>
                    <div>
                        <form action="pg_tenant.php" method="post">
                            <input type="hidden" name="cancel_confirmation" value="1">
                            <button class="btn-yes" type="submit" name="confirm_cancel">Yes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="gmap" class="gmap" onclick="closeGMapModal()" style="display: none;">
            <div class="gmap-content" onclick="event.stopPropagation();">
                <div style="display: flex; justify-content:end;">
                    <p class="gmap-close" onclick="closeGMapModal()"> &times; </p>
                </div>
                <h2>Google Map:</h2>
                <div class="map">
                    <iframe src="<?php echo $bhInfoData['gmap_link']; ?>" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>

        <!-- License Modal -->
        <div id="license" class="license" onclick="closeLicense()" style="display: none;">
            <div class="license-content" onclick="event.stopPropagation();">
                <div style="display: flex; justify-content:end;">
                    <span class="license-close" onclick="closeLicense()">&times;</span>
                </div>
                <h2>License:</h2>
                <div class="license-img">
                    <img src="../Landlord/<?php echo $bhInfoData['license']; ?>" alt="License Image">
                </div>
            </div>
        </div>

        <footer class="footer">
            &copy; 2023 Boarding House Booking. All rights reserved.
        </footer>
    </body>
</html>