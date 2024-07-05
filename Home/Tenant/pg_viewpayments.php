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

    $select_query = "SELECT * FROM payment WHERE tenant_id = $tenant_id ORDER BY transaction_no DESC";
    $payment_result = mysqli_query($conn, $select_query);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
            <title>My Payment Details</title>
            <link rel="stylesheet" type="text/css" href="../Style/style_viewpayments.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
            <link rel="icon" type="image/png" href="../images/icon.png">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
<body>
    <div class="container">
        <h2>My Payment Details</h2>

        <?php
        if (!isset($setup_message)) {
            $result = mysqli_query($conn, $select_query);

            if (mysqli_num_rows($result) > 0) {
                ?>
                <table>
                    <thead>
                        <tr>
                            <th>Transaction No</th>
                            <th>Room ID</th>
                            <th>Month</th>
                            <th>Price</th>
                            <th>Amount Paid</th>
                            <th>Date Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>{$row['transaction_no']}</td>";
                            echo "<td>{$row['room_id']}</td>";
                            echo "<td>{$row['month']}</td>";
                            echo "<td>{$row['price']}</td>";
                            echo "<td>{$row['amount_paid']}</td>";
                            echo "<td>{$row['date_paid']}</td>";
                            echo "<td>{$row['balance']}</td>";
                            echo "<td>{$row['status']}</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo "<p>No payment details available yet.</p>";
            }
        }
        ?>
    </div>
</body>

</html>
