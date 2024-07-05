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

    $bhId = $_SESSION['bh_id'];

    // Fetch data from tenant_about table where bh_id is equal to $bhId
    $tenantDataQuery = "SELECT * FROM tenant_about WHERE bh_id = ?";
    $stmt = mysqli_prepare($conn, $tenantDataQuery);
    mysqli_stmt_bind_param($stmt, "i", $bhId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tenantDataList = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    // Check if bh_id is NULL or empty
    if (empty($bhId)) {
        $noApprovedTenantsMessage = "No Approved Tenant.";
        $noPendingTenantsMessage = "No Pending Tenant.";
    } else {
        // Check if there are approved and pending tenants
        $hasApprovedTenants = false;
        $hasPendingTenants = false;

        foreach ($tenantDataList as $tenant) {
            if ($tenant['status'] === 'approved') {
                $hasApprovedTenants = true;
            } elseif ($tenant['status'] === 'pending') {
                $hasPendingTenants = true;
            }
        }

        // Set messages based on tenant status
        $noApprovedTenantsMessage = !$hasApprovedTenants ? "No Approved Tenant." : "";
        $noPendingTenantsMessage = !$hasPendingTenants ? "No Pending Tenant." : "";

        // Assuming you have a specific tenant in mind (e.g., the first one in the list)
        if (!empty($tenantDataList) && isset($tenantDataList[0]['tenant_id'])) {
            $tenantId = $tenantDataList[0]['tenant_id'];
        }
    }

    //For approving tenant
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_room_info']) && $_POST['update_room_info'] === 'true') {
            // Update the room_info table
            $tenantId = $_POST['tenant_id'];
            $roomId = $_POST['room_id'];
    
            // Update room_info table with status = 'occupied' and store tenant_id
            $updateRoomInfoQuery = "UPDATE room_info SET tenant_id = ?, room_status = 'occupied' WHERE room_id = ?";
            $stmt = mysqli_prepare($conn, $updateRoomInfoQuery);
            mysqli_stmt_bind_param($stmt, "ii", $tenantId, $roomId);
    
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            }
        } else {
            // Update the tenant_about table
            $tenantId = $_POST['tenant_id'];
            $status = "approved";  // Set the status explicitly to "approved"

            $updateQuery = "UPDATE tenant_about SET status = ? WHERE tenant_id = ?";
            $stmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($stmt, "si", $status, $tenantId);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            }
        }
    }

    // Handle updating other tenants with the same room_id to "denied"
    if (isset($_POST['update_other_tenants'])) {
        $tenantId = $_POST['tenant_id'];
        $roomId = $_POST['room_id'];

        $updateOtherTenantsQuery = "UPDATE tenant_about SET status = 'denied' WHERE room_id = ? AND tenant_id != ?";
        $stmt = mysqli_prepare($conn, $updateOtherTenantsQuery);
        mysqli_stmt_bind_param($stmt, "ii", $roomId, $tenantId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            // Additional logic if needed
            // ...
        } else {
            // Handle the error
            echo "Error updating other tenants: " . mysqli_error($conn);
        }
    }

    // Handle updating the tenant status to "kicked" and room_info status to "vacant"
    if (isset($_POST['status']) && $_POST['status'] === 'kicked' && isset($_POST['update_room_info'])) {
        $tenantId = $_POST['tenant_id'];

        // Update tenant status to "kicked" in tenant_about table
        $updateStatusQuery = "UPDATE tenant_about SET status = 'kicked' WHERE tenant_id = ?";
        $stmt = mysqli_prepare($conn, $updateStatusQuery);
        mysqli_stmt_bind_param($stmt, "i", $tenantId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            // Update room_info status to "vacant" and tenant_id to NULL
            $updateRoomInfoQuery = "UPDATE room_info SET tenant_id = NULL, room_status = 'vacant' WHERE tenant_id = ?";
            $stmt = mysqli_prepare($conn, $updateRoomInfoQuery);
            mysqli_stmt_bind_param($stmt, "i", $tenantId);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                // Additional logic if needed
                // ...
            } else {
                // Handle the error
                echo "Error updating room_info: " . mysqli_error($conn);
            }
        } else {
            // Handle the error
            echo "Error updating tenant status: " . mysqli_error($conn);
        }
    }

    // Handle updating the tenant status to "denied"
    if (isset($_POST['status']) && $_POST['status'] === 'denied') {
        $tenantId = $_POST['tenant_id'];

        $updateStatusQuery = "UPDATE tenant_about SET status = 'denied' WHERE tenant_id = ?";
        $stmt = mysqli_prepare($conn, $updateStatusQuery);
        mysqli_stmt_bind_param($stmt, "i", $tenantId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            // Additional logic if needed
            // ...
        } else {
            // Handle the error
            echo "Error updating tenant status: " . mysqli_error($conn);
        }
    }
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="stylesheet" type="text/css" href="../Style/style_mytenant.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Boarding House</title>
        <script>
            // Call showApprovedTenants initially
            showApprovedTenants();

            // Hide the "No Pending Tenant" message if it is currently displayed
            var noPendingTenantsMessage = document.getElementById('noPendingTenantsMessage');
            noPendingTenantsMessage.style.display = 'none';
            
            function showApprovedTenants() {
                document.querySelectorAll('.card').forEach(card => {
                    card.style.display = card.dataset.status === 'approved' ? 'flex' : 'none';
                });

                // Toggle button styles
                document.getElementById('showApprovedButton').classList.add('active');
                document.getElementById('showPendingButton').classList.remove('active');

                // Show the "No Approved Tenant" message if there are no approved tenants
                var noApprovedTenantsMessage = document.getElementById('noApprovedTenantsMessage');
                if (noApprovedTenantsMessage) {
                    noApprovedTenantsMessage.style.display = 'block';
                }

                // Hide the "No Pending Tenant" message if it is currently displayed
                var noPendingTenantsMessage = document.getElementById('noPendingTenantsMessage');
                if (noPendingTenantsMessage && noPendingTenantsMessage.style.display !== 'none') {
                    noPendingTenantsMessage.style.display = 'none';
                }
            }

            function showPendingTenants() {
                document.querySelectorAll('.card').forEach(card => {
                    card.style.display = card.dataset.status === 'pending' ? 'flex' : 'none';
                });

                // Toggle button styles
                document.getElementById('showApprovedButton').classList.remove('active');
                document.getElementById('showPendingButton').classList.add('active');

                // Show the "No Pending Tenant" message if there are no pending tenants
                var noPendingTenantsMessage = document.getElementById('noPendingTenantsMessage');
                if (noPendingTenantsMessage) {
                    noPendingTenantsMessage.style.display = 'block';
                }

                // Hide the "No Approved Tenant" message if it is currently displayed
                var noApprovedTenantsMessage = document.getElementById('noApprovedTenantsMessage');
                if (noApprovedTenantsMessage && noApprovedTenantsMessage.style.display !== 'none') {
                    noApprovedTenantsMessage.style.display = 'none';
                }
            }

            //Aprroved Tenant
            function approveTenant(tenantId, roomId, roomNumber, fullName) {
                var confirmationMessage = "Are you sure you want to approve " + fullName + " in Room #" + roomNumber + "? Other tenants in the same room will be denied.";

                if (confirm(confirmationMessage)) {
                    // Send AJAX request to update tenant status
                    var xhr = new XMLHttpRequest();
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === XMLHttpRequest.DONE) {
                            if (xhr.status === 200) {
                                // Update room_info table
                                updateRoomInfo(tenantId, roomId);
                            } else {
                                // Handle the error
                                console.error("Error updating tenant status");
                            }
                        }
                    };

                    xhr.open("POST", "pg_mytenant.php", true);
                    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    xhr.send("tenant_id=" + tenantId + "&status=approved");
                }
            }

            function updateRoomInfo(tenantId, roomId) {
                // Send AJAX request to update room_info table
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            // Reload the page or update the UI as needed
                            location.reload(); // This will reload the page
                        } else {
                            // Handle the error
                            console.error("Error updating room_info table");
                        }
                    }
                };

                // Update the status of other tenants with the same room_id to "denied"
                xhr.open("POST", "pg_mytenant.php", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.send("update_room_info=true&tenant_id=" + tenantId + "&room_id=" + roomId);

                // Additional AJAX request to update other tenants in the same room to "denied"
                var updateOtherTenantsXHR = new XMLHttpRequest();
                updateOtherTenantsXHR.open("POST", "pg_mytenant.php", true);
                updateOtherTenantsXHR.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                updateOtherTenantsXHR.send("update_other_tenants=true&tenant_id=" + tenantId + "&room_id=" + roomId);
            }

            //Kick Tenant
            function kickTenant(tenantId, roomNumber, fullName) {
                var confirmationMessage = "Are you sure you want to kick " + fullName + " in Room #" + roomNumber + "?";

                if (confirm(confirmationMessage)) {
                    // Send AJAX request to update tenant status to "kicked" and room_info status to "vacant"
                    var xhr = new XMLHttpRequest();
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === XMLHttpRequest.DONE) {
                            if (xhr.status === 200) {
                                // Reload the page or update the UI as needed
                                location.reload(); // This will reload the page
                            } else {
                                // Handle the error
                                console.error("Error updating tenant status and room_info");
                            }
                        }
                    };

                    xhr.open("POST", "pg_mytenant.php", true);
                    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    xhr.send("tenant_id=" + tenantId + "&status=kicked&update_room_info=true");
                }
            }

            //Deny Tenant
            function denyTenant(tenantId, roomNumber, fullName) {
                var confirmationMessage = "Are you sure you want to deny " + fullName + " in Room #" + roomNumber + "?";

                if (confirm(confirmationMessage)) {
                    // Send AJAX request to update tenant status to "denied"
                    var xhr = new XMLHttpRequest();
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === XMLHttpRequest.DONE) {
                            if (xhr.status === 200) {
                                // Reload the page or update the UI as needed
                                location.reload(); // This will reload the page
                            } else {
                                // Handle the error
                                console.error("Error updating tenant status");
                            }
                        }
                    };

                    xhr.open("POST", "pg_mytenant.php", true);
                    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    xhr.send("tenant_id=" + tenantId + "&status=denied");
                }
            }
        </script>
    </head>
    <body>
        <nav>
            <div class="logo">BH for HOME</div>
        </nav>

        <div class="main">
            <div class="sidebar">
                <div class="owner-profile" >
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
                

                <button class="sidebar-btn" style="border-radius: 0 0 50% 0;" onclick="location.href='pg_bh.php'"><i class="fas fa-home"></i> Boarding House</button>
                <button class="sidebar-btn" style="background-color: #f2f2f2; color: #140C06;" onclick="location.href='pg_mytenant.php'"><i class="fas fa-users"></i> Tenants</button>
                <div class="space" style="border-radius: 0 20% 0 0;">.</div>
                <a href="?logout=1">Logout</a>
            </div>

            <div class="main-content">
                    <h2>TENANTS</h2>
                    <div class="status-buttons">
                        <button id="showApprovedButton" onclick="showApprovedTenants()" class="active">Approved Tenants</button>
                        <button id="showPendingButton" onclick="showPendingTenants()">Pending Tenants</button>
                    </div>
                    <div class="card-container">
                        <?php if (!empty($tenantDataList)) :
                            foreach ($tenantDataList as $tenant) :
                                // Fetch room information based on room_id for each tenant
                                $roomInfoQuery = "SELECT * FROM room_info WHERE room_id = ?";
                                $stmt = mysqli_prepare($conn, $roomInfoQuery);
                                mysqli_stmt_bind_param($stmt, "i", $tenant['room_id']);
                                mysqli_stmt_execute($stmt);
                                $roomInfo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                                mysqli_stmt_close($stmt);
                                
                                if ($tenant['status'] === 'approved') {
                                    $hasApprovedTenants = true;
                                } elseif ($tenant['status'] === 'pending') {
                                    $hasPendingTenants = true;
                                } ?>

                                <?php if ($tenant['status'] === 'approved') : ?>
                                    <div class="card" data-status="<?php echo $tenant['status']; ?>">
                                        <div class="row1">
                                            <h3>Room #<?php echo $roomInfo['room_no']; ?></h3>
                                            <div class="tenant-name-img">
                                                <div style="width: 100px; margin-right: 1rem; padding: 0.5rem;">
                                                    <div class="tenant-img">
                                                        <img src="../Tenant/<?php echo $tenant['tenant_image']; ?>" alt="Tenant Image">
                                                    </div>
                                                </div>

                                                <div class="name">
                                                    <h2><?php echo $tenant['firstname'] . ' ' . substr($tenant['middlename'], 0, 1) . '.' . ' ' . $tenant['lastname']; ?></h2>
                                                    <p>Tenant ID: <?php echo $tenant['tenant_id']; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row2">
                                            <div style="margin: auto 0; display: flex;">
                                                <div class="label">
                                                    <p>Gender:</p>
                                                    <p>Birthdate:</p>
                                                    <p>Address:</p>
                                                    <p>Occupation:</p>
                                                    <p>Contact No:</p>
                                                    <p>Parents Contact:</p>
                                                </div>
                                                <div class="data" style="font-weight: bold;">
                                                    <p><?php echo $tenant['gender']; ?></p>
                                                    <p><?php echo $tenant['birthdate']; ?></p>
                                                    <p><?php echo $tenant['address']; ?></p>
                                                    <p><?php echo $tenant['occupation']; ?></p>
                                                    <p><?php echo $tenant['contact_no']; ?></p>
                                                    <p><?php echo $tenant['parents_contact']; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row3">
                                            <h2>BALANCE</h2>
                                            <p>peso</p>
                                            <div class="manage-payment">
                                                <a href="pg_payment.php?tenant_id=<?= $tenant['tenant_id']; ?>">
                                                    <button>Manage Payment</button>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="row4">
                                            <button class="kick-btn" onclick="kickTenant(<?php echo $tenant['tenant_id']; ?>, '<?php echo $roomInfo['room_no']; ?>', '<?php echo $tenant['firstname'] . ' ' . substr($tenant['middlename'], 0, 1) . '.' . ' ' . $tenant['lastname']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php elseif ($tenant['status'] === 'pending') : ?>
                                    <div class="card" data-status="<?php echo $tenant['status']; ?>">
                                        <div class="row1">
                                            <h3>Room #<?php echo $roomInfo['room_no']; ?></h3>
                                            <div class="tenant-name-img">
                                                <div style="width: 100px; margin-right: 1rem; padding: 0.5rem;">
                                                    <div class="tenant-img">
                                                        <img src="../Tenant/<?php echo $tenant['tenant_image']; ?>" alt="Tenant Image">
                                                    </div>
                                                </div>

                                                <div class="name">
                                                    <h2><?php echo $tenant['firstname'] . ' ' . substr($tenant['middlename'], 0, 1) . '.' . ' ' . $tenant['lastname']; ?></h2>
                                                    <p>Tenant ID: <?php echo $tenant['tenant_id']; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row2">
                                            <div style="margin: auto 0; display: flex;">
                                                <div class="label">
                                                    <p>Gender:</p>
                                                    <p>Birthdate:</p>
                                                    <p>Address:</p>
                                                    <p>Occupation:</p>
                                                    <p>Contact No:</p>
                                                    <p>Parents Contact:</p>
                                                </div>
                                                <div class="data" style="font-weight: bold;">
                                                    <p><?php echo $tenant['gender']; ?></p>
                                                    <p><?php echo $tenant['birthdate']; ?></p>
                                                    <p><?php echo $tenant['address']; ?></p>
                                                    <p><?php echo $tenant['occupation']; ?></p>
                                                    <p><?php echo $tenant['contact_no']; ?></p>
                                                    <p><?php echo $tenant['parents_contact']; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row3">
                                            <div class="approve-tenant">
                                                <button onclick="approveTenant(<?php echo $tenant['tenant_id']; ?>, <?php echo $tenant['room_id']; ?>, <?php echo $roomInfo['room_no']; ?>, '<?php echo $tenant['firstname'] . ' ' . substr($tenant['middlename'], 0, 1) . '.' . ' ' . $tenant['lastname']; ?>')">Approve Tenant</button>
                                                <button onclick="denyTenant(<?php echo $tenant['tenant_id']; ?>, <?php echo $roomInfo['room_no']; ?>, '<?php echo $tenant['firstname'] . ' ' . substr($tenant['middlename'], 0, 1) . '.' . ' ' . $tenant['lastname']; ?>')">Deny Tenant</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif;

                            endforeach;
                        endif; ?>

                        <!-- Display messages based on tenant status -->
                        <?php if ($noApprovedTenantsMessage) : ?>
                            <p id="noApprovedTenantsMessage"><?php echo $noApprovedTenantsMessage; ?></p>
                        <?php endif; ?>

                        <?php if ($noPendingTenantsMessage) : ?>
                            <p id="noPendingTenantsMessage" style="display: none;"><?php echo $noPendingTenantsMessage; ?></p>
                        <?php endif; ?>
                    </div>
            </div>
        </div>

        <footer class="footer">
            &copy; 2023 Boarding House Booking. All rights reserved.
        </footer>
    </body>
</html>