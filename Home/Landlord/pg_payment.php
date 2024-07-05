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
   
    // Retrieve and store the tenant_id
    $tenantId = $_GET['tenant_id'];

    // Fetch tenant information based on tenant_id
    $getTenantQuery = "SELECT * FROM tenant_about WHERE tenant_id = ?";
    $stmt = mysqli_prepare($conn, $getTenantQuery);
    mysqli_stmt_bind_param($stmt, "i", $tenantId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tenantInfo = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

     // Fetch tenant information based on tenant_id
    $getTenantRoomQuery = "SELECT * FROM room_info WHERE tenant_id = ?";
    $stmt = mysqli_prepare($conn, $getTenantRoomQuery);
    mysqli_stmt_bind_param($stmt, "i", $tenantId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tenantRoomInfo = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Fetch payment information based on tenant_id
    $getPaymentQuery = "SELECT * FROM payment WHERE tenant_id = ? ORDER BY transaction_no DESC";
    $stmt = mysqli_prepare($conn, $getPaymentQuery);
    mysqli_stmt_bind_param($stmt, "i", $tenantId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $paymentInfo = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $numTransactions = mysqli_num_rows($result);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve data from the form
        $tenantId = $_GET['tenant_id'];
        $month = $_POST['month'];
        $amountPaid = $_POST['amountPaid'];
        $datePaid = $_POST['datePaid'];
    
        // Fetch room_id and price from the database based on tenant_id
        $getRoomInfoQuery = "SELECT room_id, price FROM room_info WHERE tenant_id = ?";
        $stmt = mysqli_prepare($conn, $getRoomInfoQuery);
        mysqli_stmt_bind_param($stmt, "i", $tenantId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $roomInfo = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $price = $roomInfo['price'];

        // Calculate cumulative amount paid for the same room_id, tenant_id, and month
        $cumulativeAmountPaidQuery = "SELECT COALESCE(SUM(amount_paid), 0) AS cumulative_amount_paid FROM payment WHERE room_id = ? AND tenant_id = ? AND month = ?";
        $stmtCumulativePaid = mysqli_prepare($conn, $cumulativeAmountPaidQuery);
        mysqli_stmt_bind_param($stmtCumulativePaid, "iis", $roomInfo['room_id'], $tenantId, $month);
        mysqli_stmt_execute($stmtCumulativePaid);
        $resultCumulativePaid = mysqli_stmt_get_result($stmtCumulativePaid);
        $cumulativeAmountPaid = mysqli_fetch_assoc($resultCumulativePaid)['cumulative_amount_paid'];
        mysqli_stmt_close($stmtCumulativePaid);

        if ($cumulativeAmountPaid == 0) {
            $balance = $price - $amountPaid;
        } elseif ($cumulativeAmountPaid != 0) {
            $balance = $price - $cumulativeAmountPaid - $amountPaid;
        }

        // Calculate balance and set status
        $status = getStatus($balance, $price);

        // Validate that the amount paid is not greater than the balance
        if ($balance <= -1) {
            $errors[] = "Payment exceeds remaining balance. Balance is already 0 for the month of $month. Please enter a valid amount.";
        } else {
            // Insert data into the payment table
            $insertPaymentQuery = "INSERT INTO payment (room_id, tenant_id, month, price, amount_paid, date_paid, balance, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insertPaymentQuery);
            mysqli_stmt_bind_param($stmt, "iissdsds", $roomInfo['room_id'], $tenantId, $month, $price, $amountPaid, $datePaid, $balance, $status);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: pg_payment.php?tenant_id=" . $tenantId);
                exit();
            }            

            mysqli_stmt_close($stmt);
        }
    }

    function getStatus($balance, $price) {
        if ($balance == 0) {
            return "Paid";
        } elseif ($balance == $price) {
            return "Unpaid";
        } elseif ($balance > 0 && $balance < $price) {
            return "Partial";
        } elseif ($balance < 0) {
            return "You paid too much";
        } else {
            return "Unknown";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="stylesheet" type="text/css" href="../Style/style_payment.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manage Tenant's Payment</title>
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
                <div class="alert-messages" style="max-width: 290px; text-align: center;">
                    <?php if (!empty($errors)): ?>
                        <div class="error-container">
                            <?php foreach ($errors as $error): ?>
                                <span class="error-msg"><?php echo $error; ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <h2>Room #<?= $tenantRoomInfo['room_no'] ?></h2>

                <div class="tenant-info">
                    <div class="tenant-img-con">
                        <div style="width: 100px; margin: auto;">
                            <div class="tenant-img">
                                <img src="../Tenant/<?= $tenantInfo['tenant_image'] ?>" alt="Tenant Image">
                            </div>
                        </div>

                        <div class="name">
                            <h2><?php echo $tenantInfo['firstname'] . ' ' . substr($tenantInfo['middlename'], 0, 1) . '.' . ' ' . $tenantInfo['lastname']; ?></h2>
                            <p>Tenant ID: <?php echo $tenantInfo['tenant_id']; ?></p>
                        </div>
                    </div>

                    <div class="tenant-data">
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
                                <p><?php echo $tenantInfo['gender']; ?></p>
                                <p><?php echo $tenantInfo['birthdate']; ?></p>
                                <p><?php echo $tenantInfo['address']; ?></p>
                                <p><?php echo $tenantInfo['occupation']; ?></p>
                                <p><?php echo $tenantInfo['contact_no']; ?></p>
                                <p><?php echo $tenantInfo['parents_contact']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="room-info">
                        <h4>ROOM DETAILS</h4>
                        <div style="margin: auto; display: flex;">
                            <div class="label">
                                <p>Room No.:</p>
                                <p>Room ID:</p>
                                <p>Price:</p>
                            </div>
                            <div class="data" style="font-weight: bold;">
                                <p><?= $tenantRoomInfo['room_no'] ?></p>
                                <p><?= $tenantRoomInfo['room_id'] ?></p>
                                <p><?= $tenantRoomInfo['price'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <h2>Payment Details</h2>
                
                <div class="add-payment-con">
                    <button id="addPaymentBtn"><i class="fa fa-plus"></i> Add Payment</button>
                </div>
                
                <div class="payment-details">
                    <div class="transaction-count">
                        <p>Total transactions: <?php echo $numTransactions; ?></p>
                    </div>
                    <?php if (!empty($paymentInfo)) : ?>
                        <table>
                            <tr>
                                <th>Transaction No.</th>
                                <th>Month</th>
                                <th>Price</th>
                                <th>Amount Paid</th>
                                <th>Date Paid</th>
                                <th>balance</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            <?php mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_assoc($result)) : ?>
                                <tr>
                                    <td><?= $row['transaction_no'] ?></td>
                                    <td><?= $row['month'] ?></td>
                                    <td><?= $row['price'] ?></td>
                                    <td><?= $row['amount_paid'] ?></td>
                                    <td><?= date('F j, Y', strtotime($row['date_paid'])) ?></td>
                                    <td><?= $row['balance'] ?></td>
                                    <td><?= $row['status'] ?></td>
                                    <td>
                                        <div class="action">
                                            <button title="Edit"><i class='far fa-edit'></i></button>
                                            <button title="Delete" style="background-color: rgb(119, 0, 0); color: #f2f2f2;"><i class="far fa-trash-alt"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else : ?>
                        <p class="msg-nopayment">No payment has been made yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Add Payment Form -->
        <div id="addPaymentForm" class="form-container" style="display: none;">
            <h2>Add Payment</h2>
            <span id="amountError" class="error-message" style="font-weight: bold; color:red;"></span>
            <form id="paymentForm" action="pg_payment.php?tenant_id=<?= $tenantId; ?>" method="post">
                <div class="textbox">
                    <label for="month">Month:</label>
                    <select id="month" name="month" required>
                        <option value="" disabled selected>Select Month</option>
                        <option value="January">January</option>
                        <option value="February">February</option>
                        <option value="March">March</option>
                        <option value="April">April</option>
                        <option value="May">May</option>
                        <option value="June">June</option>
                        <option value="July">July</option>
                        <option value="August">August</option>
                        <option value="September">September</option>
                        <option value="October">October</option>
                        <option value="November">November</option>
                        <option value="December">December</option>
                    </select>
                </div>

                <label for="amountPaid">Amount Paid:</label>
                <div class="input-with-icon">
                    <i>&#8369;</i>
                    <input type="number" id="amountPaid" name="amountPaid" required>
                </div>
                
                <div class="textbox">
                    <label for="datePaid">Date Paid:</label>
                    <input type="date" id="datePaid" name="datePaid" required>
                </div>

                <div class="btns">
                    <button type="button" onclick="validatePayment()">Submit</button>
                    <button id="closeBtn">Cancel</button>
                </div>
            </form>
        </div>
        <div class="overlay" id="overlay"></div>

        <footer class="footer">
            &copy; 2023 Boarding House Booking. All rights reserved.
        </footer>

        <script>
            document.getElementById('addPaymentBtn').addEventListener('click', function() {
                console.log('Add Payment button clicked');
                document.getElementById('overlay').style.display = 'block'; // Show overlay
                document.getElementById('addPaymentForm').style.display = 'block';
            });

            // Close the form and overlay when clicking outside the form
            document.getElementById('overlay').addEventListener('click', function() {
                document.getElementById('overlay').style.display = 'none'; // Hide overlay
                document.getElementById('addPaymentForm').style.display = 'none';
            });

            document.getElementById('closeBtn').addEventListener('click', function() {
                document.getElementById('overlay').style.display = 'none'; // Hide overlay
                document.getElementById('addPaymentForm').style.display = 'none';
            });

            function validatePayment() {
                // Get the amount input value
                var amountPaid = document.getElementById("amountPaid").value;

                // Display error if the amount is zero or negative
                if (amountPaid <= 0) {
                    document.getElementById("amountError").innerHTML = "Amount paid must be greater than zero.";

                    // Hide the error message after 5 seconds
                    setTimeout(function() {
                        document.getElementById("amountError").innerHTML = "";
                    }, 5000);
                } else {
                    // Clear the error message and check if all required fields are filled in
                    document.getElementById("amountError").innerHTML = "";

                    var month = document.getElementById("month").value;
                    var datePaid = document.getElementById("datePaid").value;

                    // Check if all required fields are filled in
                    if (month.trim() === "" || datePaid.trim() === "") {
                        document.getElementById("amountError").innerHTML = "Please fill in all required fields.";

                        // Hide the error message after 5 seconds
                        setTimeout(function() {
                            document.getElementById("amountError").innerHTML = "";
                        }, 5000);
                    } else {
                        // If all fields are filled in, submit the form
                        document.getElementById("paymentForm").submit();
                        console.log("all required fields are okay."); // Check if this message is logged
                    }
                }
            }

            // Add an event listener for the Enter key press
            document.addEventListener("keydown", function (e) {
                if (e.key === "Enter") {
                    // Prevent the default behavior of the Enter key
                    e.preventDefault();

                    // Call the validatePayment function when Enter key is pressed
                    validatePayment();
                }
            });
        </script>
    </body>
</html>