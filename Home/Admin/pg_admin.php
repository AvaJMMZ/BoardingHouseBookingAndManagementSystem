<?php
    require '../conn/config.php';
    @include '../conn/config.php';
    date_default_timezone_set('Asia/Manila');

    // Handle approve and deny actions
    if (isset($_POST['approve'])) {
        $userId = $_POST['user_id'];
        $updateQuery = "UPDATE landlord_subscription SET status = 'approved', start_date = NOW(), end_date = DATE_ADD(NOW(), INTERVAL 1 YEAR) WHERE id = ?";
        $stmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        if (mysqli_stmt_execute($stmt)) {
            echo '<div class="success-message">User approved successfully!</div>';
        } else {
            echo '<div class="error-message">Error updating user status: ' . mysqli_stmt_error($stmt) . '</div>';
        }
    }

    if (isset($_POST['deny'])) {
        $userId = $_POST['user_id'];
        $denyReason = trim($_POST['deny_reason']);
    
        if (empty($denyReason)) {
            echo '<div class="error-message">Please enter a reason for denial.</div>';
        } else {
            // Update the status in the landlord_subscription table to "denied" and store the deny reason
            $updateQuery = "UPDATE landlord_subscription SET status = 'denied', denial_reason = ?, end_date = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($stmt, "si", $denyReason, $userId);
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="success-message">User denied successfully!</div>';
            } else {
                echo '<div class="error-message">Error updating user status: ' . mysqli_stmt_error($stmt) . '</div>';
            }
        }
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
            } else {
                echo '<div class="error-message">Error updating receipt path: ' . mysqli_stmt_error($stmt) . '</div>';
            }
        } else {
            echo '<div class="error-message">Error uploading receipt file.</div>';
        }
    }

    // Determine which table to display based on the clicked link
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
    } else {
        $type = "pending";
    }

    // Fetch users based on the selected type
    if ($type === "pending") {
        $query = "SELECT id, landlord_id, username, status, mode_of_payment, receipt FROM landlord_subscription WHERE status IN ('pending', 'pending renew')";
    } else if ($type === "approved") {
        $query = "SELECT landlord_id, username, start_date, end_date, mode_of_payment, receipt FROM landlord_subscription WHERE status = 'approved'";
    } else {
        die("Invalid type parameter.");
    }

    $result = mysqli_query($conn, $query);

    // Handle database query errors
    if (!$result) {
        die('<div class="error-message">Error retrieving users: ' . mysqli_error($conn) . '</div>');
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Admin Page</title>
        <link rel="stylesheet" type="text/css" href="../Style/style_admin.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script>
            $(document).ready(function() {
                // Show the modal when the "Deny" button is clicked
                $(".deny-btn").click(function() {
                    var userId = $(this).data("userid");
                    $("#denyUserId").val(userId);
                    $("#modal").show();
                });

                // Hide the modal when the "Cancel" button or modal background is clicked
                $("#modal-close, .modal").click(function(event) {
                    if ($(event.target).is("#modal-close") || $(event.target).is(".modal")) {
                        $("#modal").hide();
                    }
                });

                // Handle opening the modal and displaying payment details
                $(".view-payment-btn").click(function() {
                    var modeOfPayment = $(this).data("mode-of-payment");
                    var receipt = $(this).data("receipt");
                    var modalContent = $(".modal-content");
                    var paymentDetailsHTML = `
                        <h3 style="padding: 2%; font-size:200%">Payment Details</h3>
                        <p><strong>Payment | </strong> ${modeOfPayment}</p>
                        ${receipt === 'none' ? '<p> <strong>Receipt:</strong> <span style="color: red;">no receipt</span></p>' : `
                            <p><strong>Receipt:</strong> <a href="${receipt}" target="_blank" style="color: #45331D;">View</a></p>
                        `}
                    `;
                    modalContent.html(paymentDetailsHTML);

                    // Show the modal
                    $("#modal").show();
                });
            });
        </script>
    </head>
    <body>
        <nav>
            <div class="logo">BH for HOME</div>
            <div class="home">
                <a href="../homepage.php"> <i class="fa fa-home"></i> </a>
            </div>
            <div class="corner-links">
                <a href="pg_DenLR.php?type=denied">Denied</a> |
                <a href="pg_transactions.php?type=transactions">Transactions</a>
            </div>
        </nav>
        
        <h1>ADMIN</h1>

        <div class="main">
            <h2 class="con1">
                <div>
                    <a href="pg_admin.php?type=pending" class="link <?php echo ($type === "pending") ? "active" : ""; ?>" id="pending-link">PENDING LANDLORDS</a> |
                    <a href="pg_admin.php?type=approved" class="link <?php echo ($type === "approved") ? "active" : ""; ?>" id="approved-link"> APPROVED LANDLORDS</a>
                </div>
            </h2>
            
            <div class="card-container">
                <?php if (mysqli_num_rows($result) > 0) : ?>
                    <?php if ($type === "pending") : ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                            <div class="card">
                                <div class="tran">
                                    <div class="label">Transaction No.</div>
                                    <div class="num"><?php echo $row['id']; ?></div>
                                </div>
                                <div class="details">
                                    <?php if ($row['status'] === 'pending renew') : ?>
                                        <div class="status" style="text-align: center; text-transform:uppercase; font-weight:bold">RENEWAL</div>
                                    <?php else: ?>
                                        <div class="status" style="text-align: center; text-transform:uppercase; font-weight:bold">New Account</div>
                                    <?php endif; ?>
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
                                    <div class="action">
                                        <?php if ($row['status'] == 'pending' || $row['status'] == 'pending renew') : ?>
                                            <div class="action-b">
                                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="approve">Approve</button>
                                                </form>
                                                <button class="deny-btn" data-userid="<?php echo $row['id']; ?>">Deny</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        
                        <!-- approved landlord -->
                    <?php elseif ($type === "approved") : ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                            <div class="card">
                                <div class="app-name-id">
                                    <p class="username"><?php echo $row['username']; ?></p>
                                    <p class="id">ID: <?php echo $row['landlord_id']; ?></p>
                                </div>
                                <div class="sub">
                                    <p class="sub-label">Subscription</p>
                                    <p class="date"><?php echo date("F j, Y h:i A", strtotime($row['start_date'])); ?> &middot; <?php echo date("F j, Y h:i A", strtotime($row['end_date'])); ?> </p>
                                    
                                    <p class="stats-app">
                                        <?php
                                            $currentDate = date("Y-m-d H:i:s");
                                            $endDate = $row['end_date'];
                                            $status = ($currentDate > $endDate) ? 'expired' : 'approved';
                                            echo $status;
                                            
                                            // Update the status if the subscription has expired and its status is 'approved'
                                            if ($status === 'expired') {
                                                $landlordId = $row['landlord_id'];
                                                // Update status in landlord_subscription table
                                                $updateSubscriptionStatusQuery = "UPDATE landlord_subscription SET status = 'expired' WHERE landlord_id = ? AND status = 'approved'";
                                                $stmt = mysqli_prepare($conn, $updateSubscriptionStatusQuery);
                                                mysqli_stmt_bind_param($stmt, "i", $landlordId);
                                                
                                                if (mysqli_stmt_execute($stmt)) {
                                                    echo " (Subscription status updated to 'expired')";
                                                } else {
                                                    echo " (Error updating subscription status: " . mysqli_stmt_error($stmt) . ")";
                                                }
                                            }                                            
                                        ?>
                                    </p>
                                </div>

                                <div>
                                    <button class="view-payment-btn" title="View Payment Details"
                                            data-mode-of-payment="<?php echo $row['mode_of_payment']; ?>"
                                            data-receipt="<?php echo $row['receipt']; ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                <?php else : ?>
                    <p class="no-user">No users with <?php echo $type; ?> status.</p>
                <?php endif; ?>
            </div>

            <!-- Deny Modal -->
            <div class="modal" id="modal">
                <div class="modal-content">
                    <span class="modal-close" id="modal-close">&times;</span>
                    <h3>Deny Reason</h3>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="denyForm">
                        <input type="hidden" name="user_id" id="denyUserId" value="">
                        <div>
                            <textarea name="deny_reason" rows="4" cols="50" placeholder="Enter reason for denial..."></textarea>
                        </div>
                        <div style="margin-top: 10px;">
                            <button type="submit" name="deny">Deny</button>
                        </div>
                    </form>
                </div>
            </div>

            <footer class="footer">
                &copy; 2023 Boarding House Booking. All rights reserved.
            </footer>

        </div>
    </body>
</html>
