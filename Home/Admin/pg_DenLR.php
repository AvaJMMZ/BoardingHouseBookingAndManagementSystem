<?php
    @include '../conn/config.php';
    date_default_timezone_set('Asia/Manila');

    // Fetch denied landlords with their latest status
    $query = "SELECT l1.id, l1.landlord_id, l1.username, l1.status, l1.denial_reason, l1.end_date, l1.receipt, l1.mode_of_payment 
    FROM landlord_subscription AS l1
    LEFT JOIN landlord_subscription AS l2 ON (l1.username = l2.username AND l1.id < l2.id)
    WHERE l1.status = 'denied' AND l2.id IS NULL
    AND l1.id NOT IN (SELECT id FROM landlord_subscription WHERE status = 'approved' AND username = l1.username)";

    $result = mysqli_query($conn, $query);

    // Handle database query errors
    if (!$result) {
        die('<div class="error-message">Error retrieving denied landlords: ' . mysqli_error($conn) . '</div>');
    }

    $approvedLandlords = array();

    // Fetch denied landlords who are not already approved
    $query = "SELECT id, landlord_id, username, status, denial_reason, end_date, receipt, mode_of_payment 
    FROM landlord_subscription 
    WHERE status = 'denied' AND id NOT IN (SELECT id FROM approved_landlord_subscription)";

    // Handle approve
    if (isset($_POST['approve'])) {
    $userId = $_POST['user_id'];

    // Fetch the details of the denied landlord
    $fetchQuery = "SELECT landlord_id, username, denial_reason, end_date, receipt, mode_of_payment 
            FROM landlord_subscription 
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $fetchQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $landlordId, $username, $denialReason, $endDate, $receipt, $modeOfPayment);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Insert a new row into the approved_landlord_subscription table
    $insertQuery = "INSERT INTO landlord_subscription (landlord_id, username, status, start_date, end_date, receipt, mode_of_payment) 
            VALUES (?, ?, 'approved', NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), ?, ?)";
    $stmt = mysqli_prepare($conn, $insertQuery);
    mysqli_stmt_bind_param($stmt, "ssss", $landlordId, $username, $receipt, $modeOfPayment);
    if (mysqli_stmt_execute($stmt)) {
        echo '<div class="success-message">User approved successfully!</div>';
        echo '<script>window.location.href = "' . $_SERVER['PHP_SELF'] . '";</script>';
    } else {
        echo '<div class="error-message">Error approving user: ' . mysqli_stmt_error($stmt) . '</div>';
    }
        mysqli_stmt_close($stmt);
    }

    // Handle receipt file upload
    if (isset($_POST['upload'])) {
        $userId = $_POST['user_id'];
        $receiptFile = $_FILES['receipt_file'];
        
        $uploadDir = "../Registration/receipts/";
        $uploadPath = $uploadDir . basename($receiptFile['name']);
        $uploadStatus = move_uploaded_file($receiptFile['tmp_name'], $uploadPath);

        if ($uploadStatus) {
            $updateQuery = "UPDATE landlord_subscription SET receipt = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($stmt, "si", $uploadPath, $userId);
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="success-message">Receipt uploaded successfully!</div>';
                echo '<script>window.location.href = "' . $_SERVER['PHP_SELF'] . '";</script>';
            } else {
                echo '<div class="error-message">Error updating receipt path: ' . mysqli_stmt_error($stmt) . '</div>';
            }
        } else {
            echo '<div class="error-message">Error uploading receipt file.</div>';
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Denied Landlords</title>
        <link rel="stylesheet" type="text/css" href="../Style/style_admin.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <script>
            function confirmApproval(username) {
                var confirmation = confirm("Are you sure you want to approve '" + username + "'?");
                if (confirmation) {
                    location.reload();
                }
                return confirmation;
            }

            // View Denial Details
            function openDenialModal(denialReason, deniedDate) {
                const modal = document.getElementById("denialModal");
                const denialReasonElement = document.getElementById("denialReason");
                const denialDateElement = document.getElementById("denialDate");

                denialReasonElement.textContent = denialReason;
                denialDateElement.textContent = "Denied Date: " + deniedDate;

                modal.style.display = "block";
            }

            function closeDenialModal() {
                const modal = document.getElementById("denialModal");
                modal.style.display = "none";
            }
        </script>
    </head>
    <body>
        <nav>
            <div class="back-link">
                <a class="back-link" href="pg_admin.php"><i class="fa fa-arrow-circle-left"></i> Back</a>
            </div>
        </nav>
        
        <h2 class="transaction-title">DENIED LANDLORDS</h2>
        
        <div id="resultTable">
            <?php if (mysqli_num_rows($result) > 0) : ?>
                <div class="main" style="padding: 2%; flex-direction: row; margin-top: 2%; margin-bottom: 2%;">
                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                        <div class="card">
                            <div class="tran">
                                <div class="label">Transaction No.</div>
                                <div class="num"><?php echo $row['id']; ?></div>
                            </div>
                            <div class="details">
                                <div class="info">
                                    <div class="name-id">
                                        <p class="username"><?php echo $row['username']; ?></p>
                                        <p class="id">ID: <?php echo $row['landlord_id']; ?></p>
                                    </div>
                                    <div class="payment ">
                                        <?php if (!empty($row['receipt']) && $row['receipt'] !== 'none') : ?>
                                        
                                            <div class="receipt">
                                                <p class="rec-label" style="padding: 5px;">Payment | <?php echo $row['mode_of_payment']; ?></p>
                                                <div class="rec-view" style="display: flex; flex-direction: row; padding: 5px;">
                                                    <p style="padding-right: 10%;">Receipt:</p>
                                                    <a href="<?php echo $row['receipt']; ?>" target="_blank" style="color: #45331D;">View</a>
                                                </div>
                                            </div>
                                        <?php elseif ($row['receipt'] == 'none') : ?>
                                            <div class="no-receipt">
                                                <div class="payment-label-noreceipt">
                                                    <p class="payment" style="padding: 5px;">Payment | <?php echo $row['mode_of_payment']; ?></p>
                                                    <p class="receipt" style="padding: 5px; display:flex; flex-direction: row;">Receipt:&nbsp;<span style="color: red; font-weight: bold;">no receipt</span></p>
                                                </div>
                                                <div class="upload" style="padding: 5px; border-left: 1px solid #140C06;">
                                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                        <div>
                                                            <input type="file" name="receipt_file" approve="image/*">
                                                        </div>
                                                        <div style="margin-top: 10px;">
                                                            <button type="submit" name="upload">Upload</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>   
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="action-b">
                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return confirmApproval('<?php echo $row['username']; ?>')">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="approve" class="approve-btn">Approve</button>                                    
                                    </form>
                                </div>
                            </div>
                            <div>
                                <button class="view-denial-btn" title="View Denial Details" onclick="openDenialModal('<?php echo $row['denial_reason']; ?>', '<?php echo date("F j, Y | h:i A", strtotime($row['end_date'])); ?>')"><i class="fa fa-info-circle"></i></button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    </div>
            <?php else : ?>
                <p class="no-user">No Denied User.</p>
            <?php endif; ?>
        </div>

        

        <!-- Hidden modal to display the denial details -->
        <div id="denialModal" class="modal">
            <div class="modal-content" style="border-radius: 50%; box-shadow: 0 2px 4px #140C06;">
                <span class="close" onclick="closeDenialModal()">&times;</span>
                <div class="modal-header">
                    <h2 style="color: black;">Denial Details</h2>
                </div>
                <div class="modal-body">
                    <div class="denied-date" id="denialDate"></div>
                    <div class="DR-label">Denial Reason:</div>
                    <div class="reason" id="denialReason"></div>
                </div>
            </div>
        </div>

        <footer class="footer">
            &copy; 2023 Boarding House Booking. All rights reserved.
        </footer>
    </body>
</html>
