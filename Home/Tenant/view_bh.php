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

    // Retrieve the selected bh_id from the URL parameter
    if (isset($_GET['bh_id'])) {
        $selectedBhId = $_GET['bh_id'];
    } else {
        echo "No bh_id selected.";
        exit();
    }

    // Fetch data from the bh_info table for the selected bh_id
    $bhInfoQuery = "SELECT * FROM bh_info WHERE bh_id = $selectedBhId";
    $bhInfoResult = mysqli_query($conn, $bhInfoQuery);
    $bhInfoData = mysqli_fetch_assoc($bhInfoResult);

    // Fetch data from the owner_about table for the selected landlord_id
    $landlordId = $bhInfoData['landlord_id'];
    $ownerAboutQuery = "SELECT * FROM owner_about WHERE landlord_id = $landlordId";
    $ownerAboutResult = mysqli_query($conn, $ownerAboutQuery);
    $ownerAboutData = mysqli_fetch_assoc($ownerAboutResult);

    // Fetch data from the room_info table for the selected bh_id
    $roomQuery = "SELECT * FROM room_info WHERE bh_id = $selectedBhId";
    $roomResult = mysqli_query($conn, $roomQuery);
    $room = mysqli_fetch_assoc($roomResult);

    if (isset($_POST['tenant_id']) && isset($_POST['room_id'])) {
        // Get the tenant_id and room_id from the AJAX request
        $tenantId = $_POST['tenant_id'];
        $roomId = $_POST['room_id'];
    
        // Get the bh_id from the existing data
        $bhId = $bhInfoData['bh_id'];
    
        // Update the tenant_about table (replace this with your actual update query)
        $updateQuery = "UPDATE tenant_about SET status = 'pending', bh_id = $bhId, room_id = $roomId WHERE tenant_id = $tenantId";
        mysqli_query($conn, $updateQuery);
    
        // Close the database connection
        mysqli_close($conn);
    
        // Send a response if needed
        echo "Database updated successfully";
        exit(); 
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>View Boarding House</title>
        <link rel="stylesheet" type="text/css" href="../Style/style_view-bh.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script>
            function confirmGetRoom(roomNo, bhName, roomId, roomStatus) {
                if (roomStatus === "occupied") {
                    // Display a centered alert message indicating that the room is already occupied
                    var alertBox = document.createElement("div");
                    alertBox.className = "centered-alert";
                    alertBox.innerHTML = "This room is already occupied. Only vacant rooms can be selected.";
                    document.body.appendChild(alertBox);

                    // Remove the alert after a certain duration (e.g., 3 seconds)
                    setTimeout(function () {
                        document.body.removeChild(alertBox);
                    }, 5000);
                } else if (roomStatus === "vacant") {
                    var confirmationModal = document.getElementById("confirmationModal");
                    var overlay = document.getElementById("overlay");
                    var confirmationMessage = document.getElementById("confirmationMessage");

                    // Set the message dynamically
                    confirmationMessage.innerHTML = "Are you sure you want to get Room #" + roomNo + " in " + bhName + "?";

                    // Set the selected room_id in a data attribute
                    confirmationModal.setAttribute("data-room-id", roomId);

                    // Display the modal and overlay
                    confirmationModal.style.display = "block";
                    overlay.style.display = "block";
                }
            }

            function closeConfirmation() {
                var confirmationModal = document.getElementById("confirmationModal");
                var overlay = document.getElementById("overlay");

                // Hide the modal and overlay
                confirmationModal.style.display = "none";
                overlay.style.display = "none";
            }

            function handleConfirmation() {
                var tenantId = <?php echo $tenant_data['tenant_id']; ?>;
                var roomId = document.getElementById("confirmationModal").getAttribute("data-room-id");

                // Ensure that roomId is not null or undefined
                if (roomId !== null && roomId !== undefined) {
                    // Make an AJAX request to update the database
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "", true);
                    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

                    xhr.onreadystatechange = function () {
                        if (xhr.readyState == 4 && xhr.status == 200) {
                            window.location.href = "pg_tenant.php";
                            console.log(xhr.responseText);
                        }
                    };

                    // Send tenant_id and room_id to the same PHP script (view_bh.php)
                    xhr.send("tenant_id=" + tenantId + "&room_id=" + roomId);

                    // Close the confirmation modal
                    closeConfirmation();
                } else {
                    console.log("Error: Room ID is not defined.");
                }
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
            <div class="landlord" style="display: flex;">
                <div style="margin: 1% 0 0 5%;;">
                    <img class="owner-image" src="../Landlord/<?php echo $ownerAboutData['landlord_image']; ?>" alt="Owner Image">
                    <h3 style="margin-left: 1rem;">OWNER</h3>
                </div>
                <div style="margin: 1% 0 0 5%;">
                    <h1><?php echo $ownerAboutData['firstname'] . ' ' . $ownerAboutData['middlename'] . ' ' . $ownerAboutData['lastname']; ?></h1>
                    <div style="display: flex; justify-content: space-between;">
                        <div class="label">
                            <p>Contact No:</p>
                            <p>Gender:</p>
                            <p>Address:</p>
                        </div>
                        <div class="data" style="font-weight: bold; text-transform: capitalize;">
                            <p><?php echo $ownerAboutData['contact_no']; ?></p>
                            <p><?php echo $ownerAboutData['gender']; ?></p>
                            <p><?php echo $ownerAboutData['address']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div style="display: flex;">
                <div class="bh" style="flex: 1;">
                    <h1><?php echo $bhInfoData['business_name']; ?></h1>
                    <img class="bh-image" src="../Landlord/<?php echo $bhInfoData['bh_img']; ?>" alt="Owner Image">
                    <div class="bh-details">
                        <div style="display: flex; justify-content: space-between;">
                            <div class="label">
                                <p>Location:</p>
                                <p>Landmark:</p>
                                <p>Number of Rooms:</p>
                                <p>License:</p>
                                <p>Description:</p>
                            </div>
                            <div class="data">
                                <p><?php echo $bhInfoData['location']; ?></p>
                                <p><?php echo $bhInfoData['landmark']; ?></p>
                                <p><?php echo $bhInfoData['no_rooms']; ?></p>
                                <p><a href="../Landlord/<?php echo $bhInfoData['license']; ?>" target="_blank">View</a></p>
                                <p><?php echo $bhInfoData['description']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="room-con" style="flex: 1;">
                    <h1>ROOMS</h1>
                    <div class="card-con">
                        <?php mysqli_data_seek($roomResult, 0); while ($room = mysqli_fetch_assoc($roomResult)) { ?>
                            <div class="room-card">
                                <div style="display: flex;">
                                    <div style="flex:1;">
                                        <div class="num-id">
                                                <p>Room #<?php echo $room['room_no']; ?> &middot; ₱<?php echo $room['price']; ?></p>
                                        </div>

                                        <div class="tenant">
                                            <?php
                                                if (empty($room['tenant_id'])) {
                                                    echo '<p class="v-status">' . $room['room_status'] . '</p>';
                                                } elseif (!empty($room['tenant_id'])) {
                                                    echo '<div style="display: flex; justify-content: space-evenly;"';
                                                    echo '<p class="o-status">OCCUPIED</p> &middot; <p class="tenant-occ">Tenant ID: ' . $room['tenant_id'] . '</p>';
                                                    echo '</div>';
                                                }
                                            ?>
                                        </div>
                                    </div>

                                    <div class="GTR-con">
                                        <button class="btn-GTR" onclick="confirmGetRoom(<?php echo $room['room_no']; ?>, '<?php echo $bhInfoData['business_name']; ?>', <?php echo $room['room_id']; ?>, '<?php echo $room['room_status']; ?>')">Get this Room</button>
                                    </div>
                                </div>

                                <div>
                                    <div style="display: flex;">
                                        <div class="img">
                                            <div class="room-image">
                                                <img id="img-1" src="../Landlord/<?php echo $room['room_img_1']; ?>" alt="Room Image 1" onclick="showImageGallery(1)">
                                                <img id="img-2" src="../Landlord/<?php echo $room['room_img_2']; ?>" alt="Room Image 2" onclick="showImageGallery(2)">
                                                <img id="img-3" src="../Landlord/<?php echo $room['room_img_3']; ?>" alt="Room Image 3" onclick="showImageGallery(3)">
                                            </div>
                                        </div>
                                        
                                        <div class="details">
                                            <div class="capsule"><p>Bed &middot; <?php echo $room['bed']; ?></p></div>
                                            <div class="capsule"><p>Light &middot; <?php echo $room['light']; ?></p></div>
                                            <div class="capsule"><p>Outlet &middot; <?php echo $room['outlet']; ?></p></div>
                                            <div class="capsule"><p>Table &middot; <?php echo $room['tables']; ?></p></div>
                                            <div class="capsule"><p>Chair &middot; <?php echo $room['chair']; ?></p></div>
                                            <div class="capsule"><p>Air Conditioner &middot; <?php echo $room['aircon']; ?></p></div>
                                            <div class="capsule"><p>Electric Fan &middot; <?php echo $room['electricfan']; ?></p></div>
                                        </div>
                                    </div>
                                    
                                    <div class="room-info">
                                        <p class="des-label">Room Description:</p>
                                        <div class="room-description">
                                            <p><?php echo $room['room_description']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="gmap">
                <p>Google Map: </p>
                <div class="map">
                    <iframe src="<?php echo $bhInfoData['gmap_link']; ?>" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>

        <!-- Get this Room -->
        <div id="confirmationModal" class="confirmation-modal">
            <div id="confirmationMessage" class="confirmation-message"></div>
            <div class="confirmation-buttons">
                <button id="confirmOkButton" onclick="handleConfirmation()">OK</button>
                <button onclick="closeConfirmation()">Cancel</button>
            </div>
        </div>
        <div id="overlay" class="overlay"></div>

        <footer class="footer">
            &copy; 2023 Boarding House Booking. All rights reserved.
        </footer>
    </body>
</html>