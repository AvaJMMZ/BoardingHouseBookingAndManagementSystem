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

    $bh_id = $_SESSION['bh_id'];

    // Check if bh_id exists in the room_info table
    $sql = "SELECT * FROM room_info WHERE bh_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $bh_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $roomInfo = array(); // Initialize an empty array to store all room information

    if ($result->num_rows > 0) {
        // Fetch all rows and store them in the $roomInfo array
        while ($row = $result->fetch_assoc()) {
            $roomInfo[] = $row;
        }
    } else {
        // No room information exists
        $roomInfo = null;
    }

    function getNextRoomNumber($roomInfo) {
        // Initialize the next room number
        $nextRoomNumber = 1;
    
        if ($roomInfo !== null) {
            // Get an array of existing room numbers
            $existingRoomNumbers = array_column($roomInfo, 'room_no');
    
            // Find the maximum existing room number
            $maxRoomNumber = max($existingRoomNumbers);
    
            // Calculate the next room number
            $nextRoomNumber = $maxRoomNumber + 1;
        }
    
        return $nextRoomNumber;
    } 

    // Delete Room
    if (isset($_GET['room_id'])) {
        // Get the room ID from the URL parameter
        $roomId = $_GET['room_id'];

        // First, calculate the room count
        $selectQuery = "SELECT * FROM room_info WHERE bh_id = ?";
        $cstmt = $conn->prepare($selectQuery);
        $cstmt->bind_param("i", $bh_id);

        if ($cstmt->execute()) {
            $result = $cstmt->get_result();
            $roomCount = $result->num_rows-1;

            // Update no_rooms in bh_info table
            $updateQuery = "UPDATE bh_info SET no_rooms = ? WHERE bh_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ii", $roomCount, $bh_id);

            if ($updateStmt->execute()) {
                echo "Updated no_rooms in bh_info with " . $roomCount . " rooms.<br>";
            } else {
                // Handle the update error
                $errors[] = "Error updating no_rooms in bh_info: " . $updateStmt->error;
            }
        } else {
            $errors[] = "Error executing the query to calculate room count: " . $cstmt->error;
        }

        // Delete the room from the database
        $deleteSql = "DELETE FROM room_info WHERE room_id = ?";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bind_param("i", $roomId);

        if ($stmt->execute()) {
            // Room deleted successfully, you can show a success message
            $_SESSION['success-message'] = "Room deleted successfully!";
        } else {
            // Error occurred while deleting the room
            $_SESSION['error-message'] = "Error deleting room: " . $conn->error;
        }

        // Redirect back to the room management page
        header("Location: pg_room.php");
        exit();
    }

    // Handle form submission for adding/editing rooms
    if (isset($_POST['addRoom'])) {
        $roomNumber = $_POST['roomNumber'];
        $price = $_POST['price'];
        $bed = $_POST['bed'];
        $light = $_POST['light'];
        $outlet = $_POST['outlet'];
        $tables = $_POST['tables'];
        $chair = $_POST['chair'];
        $aircon = $_POST['aircon'];
        $electricFan = $_POST['electricFan'];
        $roomDescription = $_POST['roomDescription'];
    
        // Handle room image uploads
        $imagePaths = [];
    
        for ($i = 1; $i <= 3; $i++) {
            $imagePath = "RoomImages/";
    
            // Check if an image file was uploaded
            if (isset($_FILES['roomImage' . $i]) && !empty($_FILES['roomImage' . $i]['name'])) {
                $imageFile = $_FILES['roomImage' . $i];
                $imageName = $imageFile['name'];
                $imageTmpName = $imageFile['tmp_name'];
                $imagePath .= $imageName;
    
                // Move the uploaded image to the desired directory
                if (move_uploaded_file($imageTmpName, $imagePath)) {
                    $imagePaths[] = $imagePath;
                } else {
                    $errors[] = "Failed to upload image " . $i;
                }
            }
        }
    
        // First, calculate the room count
        $selectQuery = "SELECT * FROM room_info WHERE bh_id = ?";
        $cstmt = $conn->prepare($selectQuery);
        $cstmt->bind_param("i", $bh_id);
    
        if ($cstmt->execute()) {
            $result = $cstmt->get_result();
            $roomCount = $result->num_rows+1;
    
            // Update no_rooms in bh_info table
            $updateQuery = "UPDATE bh_info SET no_rooms = ? WHERE bh_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ii", $roomCount, $bh_id);
    
            if ($updateStmt->execute()) {
                echo "Updated no_rooms in bh_info with " . $roomCount . " rooms.<br>";
            } else {
                // Handle the update error
                $errors[] = "Error updating no_rooms in bh_info: " . $updateStmt->error;
            }
        } else {
            $errors[] = "Error executing the query to calculate room count: " . $cstmt->error;
        }
    
        // Then, insert room data into the room_info table
        $insertSql = "INSERT INTO room_info (bh_id, room_no, price, bed, light, outlet, tables, chair, aircon, electricfan, room_description, room_img_1, room_img_2, room_img_3) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("iiiiiiiiiissss", $bh_id, $roomNumber, $price, $bed, $light, $outlet, $tables, $chair, $aircon, $electricFan, $roomDescription, $imagePaths[0], $imagePaths[1], $imagePaths[2]);
    
        if ($stmt->execute()) {
            // Room added successfully, you can show a success message
            $_SESSION['success-message'] = "Room added successfully!";
        } else {
            // Error occurred while adding the room
            $errors[] = "Error adding room: " . $conn->error;
        }
    
        header("Location: pg_room.php");
        exit();
    } elseif (isset($_POST['saveChanges'])) {
        // Edit Room logic
        $editRoomId = $_POST['editRoomId']; // You need to add a hidden input field for room ID in the edit form

        // Validate and sanitize the form data
        $editRoomNumber = $_POST['editRoomNumber'];
        $editPrice = $_POST['editPrice'];
        $editBed = $_POST['editBed'];
        $editLight = $_POST['editLight'];
        $editOutlet = $_POST['editOutlet'];
        $editTables = $_POST['editTables'];
        $editChair = $_POST['editChair'];
        $editAircon = $_POST['editAircon'];
        $editElectricFan = $_POST['editElectricFan'];
        $editRoomDescription = $_POST['editRoomDescription'];

        // Update room data in the room_info table
        $updateSql = "UPDATE room_info SET room_no=?, price=?, bed=?, light=?, outlet=?, tables=?, chair=?, aircon=?, electricfan=?, room_description=? WHERE room_id=?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("iiiiiiiiisi", $editRoomNumber, $editPrice, $editBed, $editLight, $editOutlet, $editTables, $editChair, $editAircon, $editElectricFan, $editRoomDescription, $editRoomId);

        if ($stmt->execute()) {
            // Room updated successfully, you can show a success message
            $_SESSION['success-message'] = "Room updated successfully!";
        } else {
            // Error occurred while updating the room
            $errors[] = "Error updating room: " . $conn->error;
        }

        header("Location: pg_room.php");
        exit();
    }

    // Check if there are rooms in the $roomInfo array
    if (!empty($roomInfo)) {
        // Define a custom sorting function
        function sortByRoomNumber($a, $b) {
            return $a['room_no'] - $b['room_no'];
        }

        // Sort the $roomInfo array by room number using usort
        usort($roomInfo, 'sortByRoomNumber');
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Manage Rooms</title>
        <link rel="stylesheet" type="text/css" href="../Style/style_room.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script>
            function closeSuccessMessage() {
                var successMessage = document.querySelector('.success-message');
                successMessage.style.display = 'none';
            }

            function closeErrorMessage() {
                var successMessage = document.querySelector('.error-message');
                successMessage.style.display = 'none';
            }

            function showAddRoom() {
                var form = document.getElementById('addRoomForm');
                form.style.display = 'block';
            }

            function hideAddRoom() {
                var form = document.getElementById('addRoomForm');
                form.style.display = 'none';
            }

            function confirmDelete(roomId) {
                var confirmDelete = confirm("Are you sure you want to delete room #" + roomId + "?");
                if (confirmDelete) {
                    // Redirect to the PHP script to delete the room
                    window.location.href = "pg_room.php?room_id=" + roomId;
                }
            }

            function showRoomDetails(roomId) {
                // Find the room information based on roomId
                const room = <?php echo json_encode($roomInfo); ?>;
                const roomDetails = room.find((r) => r.room_id === roomId);

                if (roomDetails) {
                    // Create a modal or overlay to display the room details
                    const modal = document.createElement('div');
                    modal.classList.add('modal');

                    const content = document.createElement('div');
                    content.classList.add('modal-content');

                    // Display room details inside the modal
                    content.innerHTML = `
                        <h2>Edit Room #${roomDetails.room_no}</h2>
                        <form action="pg_room.php?edit=1" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="editRoomId" value="${roomDetails.room_id}">
                                    <div style="display: flex;">
                                        <div style="flex: 1;">
                                            <div class="textbox">
                                                <label for="editRoomNumber">Room Number:</label>
                                                <input type="number" id="editRoomNumber" name="editRoomNumber" value="${roomDetails.room_no}" required>
                                            </div>
                                            <div class="textbox">
                                                <label for="editPrice">Price:</label>
                                                <input type="number" id="editPrice" name="editPrice" value="${roomDetails.price}" required>
                                            </div>
                                            <div class="textbox">
                                                <label for="editBed">Number of Bed:</label>
                                                <input type="number" id="editBed" name="editBed" value="${roomDetails.bed}" required>
                                            </div>
                                            <div class="textbox">
                                                <label for="editLight">Number of Light:</label>
                                                <input type="number" id="editLight" name="editLight" value="${roomDetails.light}" required>
                                            </div>
                                            <div class="textbox">
                                                <label for "editOutlet">Number of Outlet:</label>
                                                <input type="number" id="editOutlet" name="editOutlet" value="${roomDetails.outlet}" required>
                                            </div>
                                            <div class="textbox">
                                                <label for="editTables">Number of Table:</label>
                                                <input type="number" id="editTables" name="editTables" value="${roomDetails.tables}" required>
                                            </div>
                                            <div class="textbox">
                                                <label for="editChair">Number of Chair:</label>
                                                <input type="number" id="editChair" name="editChair" value="${roomDetails.chair}" required>
                                            </div>
                                            <div class="textbox">
                                                <label for="editAircon">Number of Air Con.:</label>
                                                <input type="number" id="editAircon" name="editAircon" value="${roomDetails.aircon}" required>
                                            </div>
                                            <div class="textbox">
                                                <label for="editElectricFan">Number of Fan:</label>
                                                <input type="number" id="editElectricFan" name="editElectricFan" value="${roomDetails.electricfan}" required>
                                            </div>
                                        </div>
                                        <div class="img-input">
                                            <div class="des-input" style="display: flex; flex-direction: column;">
                                                <label for="editRoomDescription">Room Description:</label>
                                                <textarea id="editRoomDescription" name="editRoomDescription" rows="4">${roomDetails.room_description}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="frm-button">
                                        <button type="submit" name="saveChanges">Save Changes</button>
                                    </div>
                        </form>
                    `;

                    const closeButton = document.createElement('button');
                    closeButton.textContent = 'Close';
                    closeButton.addEventListener('click', () => {
                        document.body.removeChild(modal);
                    });

                    content.appendChild(closeButton);
                    modal.appendChild(content);

                    document.body.appendChild(modal);
                }
            }

            function hideRoomEdit() {
                document.getElementById("editRoomForm").style.display = "none";
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
                <?php if ($roomInfo !== null) : ?>
                    <h2>Rooms</h2>
                    <?php foreach ($roomInfo as $room) : ?>
                        <div class="card-con">
                            <div class="room-card">
                                <div class="img">
                                    <div class="room-image">
                                        <img id="img-1" src="<?php echo $room['room_img_1']; ?>" alt="Room Image 1">
                                        <img id="img-2" src="<?php echo $room['room_img_2']; ?>" alt="Room Image 2">
                                        <img id="img-3" src="<?php echo $room['room_img_3']; ?>" alt="Room Image 3">
                                    </div>
                                    <div class="img-nav">
                                        <a href="img-1"></a>
                                        <a href="img-2"></a>
                                        <a href="img-3"></a>
                                    </div>
                                </div>
                                <div class="room-info">
                                    <div style="display: flex;">
                                        <div class="num-id">
                                            <p>Room #<?php echo $room['room_no']; ?> &middot; <span style="font-weight: normal; font-style: italic;">ID: <?php echo $room['room_id']; ?></span> &middot; ₱<?php echo $room['price']; ?></p>
                                        </div>
                                        <div class="action">
                                            <button onclick="showRoomDetails(<?php echo $room['room_id']; ?>)"><i class="fas fa-edit"></i></button>
                                            <button title="Delete Room" style="color: darkred;" onclick="confirmDelete(<?php echo $room['room_id']; ?>)"><i class="fas fa-trash"></i></button>
                                        </div>
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
                                    <div class="details">
                                        <div class="capsule"><p>Bed &middot; <?php echo $room['bed']; ?></p></div>
                                        <div class="capsule"><p>Light &middot; <?php echo $room['light']; ?></p></div>
                                        <div class="capsule"><p>Outlet &middot; <?php echo $room['outlet']; ?></p></div>
                                        <div class="capsule"><p>Table &middot; <?php echo $room['tables']; ?></p></div>
                                        <div class="capsule"><p>Chair &middot; <?php echo $room['chair']; ?></p></div>
                                        <div class="capsule"><p>Air Conditioner &middot; <?php echo $room['aircon']; ?></p></div>
                                        <div class="capsule"><p>Electric Fan &middot; <?php echo $room['electricfan']; ?></p></div>
                                    </div>
                                    <p class="des-label">Room Description:</p>
                                    <div class="room-description">
                                        <p><?php echo $room['room_description']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button onclick="showAddRoom()" id="add-button"><i class="fas fa-plus"></i></button>
                <?php else : ?>
                    <p class="no-room">There are no rooms yet.</p>
                    <button onclick="showAddRoom()" id="add-button"><i class="fas fa-plus"></i></button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Form to add a new room -->
        <div class="addroom" id="addRoomForm" style="display: none;">
            <div class="addroom-content">
                <h3>Add Room</h3>
                <form action="pg_room.php" method="POST" enctype="multipart/form-data">
                    <div style="display: flex;">
                        <div style="flex: 1;">
                            <div class="textbox">
                                <label for="roomNumber">Room Number:</label>
                                <input type="number" id="roomNumber" name="roomNumber" value="<?php echo getNextRoomNumber($roomInfo); ?>" required><br><br>
                            </div>

                            <div class="textbox">
                                <label for="price">Price:</label>
                                <input type="number" id="price" name="price" required><br><br>
                            </div>

                            <div class="textbox">
                                <label for="bed">Number of Bed:</label>
                                <input type="number" id="bed" name="bed" required><br><br>
                            </div>

                            <div class="textbox">
                                <label for="light">Number of Light:</label>
                                <input type="number" id="light" name="light" required><br><br>
                            </div>

                            <div class="textbox">
                                <label for="outlet">Number of Outlet:</label>
                                <input type="number" id="outlet" name="outlet" required><br><br>
                            </div>

                            <div class="textbox">
                                <label for="tables">Number of Table:</label>
                                <input type="number" id="tables" name="tables" required><br><br>
                            </div>

                            <div class="textbox">
                                <label for="chair">Number of Chair:</label>
                                <input type="number" id="chair" name="chair" required><br><br>
                            </div>

                            <div class="textbox">
                                <label for="aircon">Number of Air Con.:</label>
                                <input type="number" id="aircon" name="aircon" required><br><br>
                            </div>

                            <div class="textbox">
                                <label for="electricFan">Number of Fan:</label>
                                <input type="number" id="electricFan" name="electricFan" required><br><br>
                            </div>
                        </div>
                        
                        <div class="img-input">
                            <label for="roomImage1">Room Image 1:</label>
                            <input type="file" id="roomImage1" name="roomImage1" accept="image/*" required><br><br>

                            <label for="roomImage2">Room Image 2:</label>
                            <input type="file" id="roomImage2" name="roomImage2" accept="image/*" required><br><br>

                            <label for="roomImage3">Room Image 3:</label>
                            <input type="file" id="roomImage3" name="roomImage3" accept="image/*" required><br><br>

                            <div class="des-input" style="display: flex; flex-direction:column;">
                                <label for="roomDescription">Room Description:</label>
                                <textarea id="roomDescription" name="roomDescription" rows="4"></textarea><br><br>
                            </div>
                        </div>
                    </div>

                    <div class="frm-button">
                        <button type="submit" name="addRoom">Add Room</button>
                        <button type="button" onclick="hideAddRoom()">Cancel</button>
                    </div>

                    
                </form>
            </div>
        </div>

        <footer class="footer">
            &copy; 2023 Boarding House Booking. All rights reserved.
        </footer>
    </body>
</html>