<?php
    session_start();
    @include '../conn/config.php';
    $error = array();
    $success_msg = "";
    $payment_mode = '';

    if (!isset($_SESSION['registration_data'])) {
        header("Location: frm_registration.php");
        exit();
    }

    if (isset($_POST['payment'])) {
        if (isset($_SESSION['registration_data'])) {
            // Retrieve registration data from session
            $registration_data = $_SESSION['registration_data'];
            $username = $registration_data['username'];
            $email = $registration_data['email'];
            $password = $registration_data['password'];
            $user_type = $registration_data['user_type'];

            // Retrieve payment details from form
            $payment_mode = $_POST['payment_mode'];
            $receipt_image = $_FILES['receipt_image']['name'];
            $receipt_temp = $_FILES['receipt_image']['tmp_name'];

            if ($payment_mode == 'Gcash') {
                // Check if receipt is uploaded
                if (empty($receipt_image)) {
                    $error[] = 'Receipt image is required for Gcash payment.';
                } else {
                    // Move the uploaded receipt image to the "receipts" directory
                    $receipt_destination = '../Registration/receipts/' . $receipt_image;
                    move_uploaded_file($receipt_temp, $receipt_destination);
                }
            } else {
                // For in-person payment, set receipt destination as "none"
                $receipt_destination = "none";
            }

            if (empty($error)) {
                // Insert the new user and payment details into the database
                $status = ($user_type == 'landlord') ? "pending" : null;

                // Insert into user_info table
                $insert_user = "INSERT INTO user_info (username, email, password, user_type) 
                                VALUES ('$username', '$email', '$password', '$user_type')";
                mysqli_query($conn, $insert_user);

                // Retrieve the last inserted user ID
                $user_id = mysqli_insert_id($conn);

                // Insert into landlord_subscription table
                $insert_subscription = "INSERT INTO landlord_subscription (landlord_id, username, start_date, end_date, status, mode_of_payment, receipt) 
                                        VALUES ('$user_id', '$username', '', '', '$status', '$payment_mode', '$receipt_destination')";
                if (mysqli_query($conn, $insert_subscription)) {
                    $success_msg = "Registered and payment completed successfully! Please wait for admin's approval.";

                    // Clear registration data from session
                    unset($_SESSION['registration_data']);

                    // Display pop-up message
                    echo '<script>alert("Registered and payment completed successfully! Please wait for admin\'s approval."); 
                        window.location.href = "frm_login.php";</script>';
                    exit();
                } else {
                    $error[] = 'Registration failed. Please try again. Error: ' . mysqli_error($conn);
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Payment Form</title>
        <link rel="stylesheet" type="text/css" href="../Style/style_form.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script>
            setTimeout(function() {
                var errorMessages = document.getElementsByClassName('error-msg');
                while (errorMessages[0]) {
                    errorMessages[0].parentNode.removeChild(errorMessages[0]);
                }
            }, 10000); // 10 seconds
        </script>
    </head>
    <body>
        <nav>
            <div class="back-link">
                <a class="back-link" href="frm_registration.php"><i class="fa fa-arrow-circle-left"></i> Back</a>
            </div>
        </nav>

        <div class="pay-main">
            <div class="instruction-con">
                <div class="payment-instructions">
                    <div class="pay-header">
                        <h2 style="font-weight: 900;">PAYMENT INSTRUCTION</h2> 
                        <p>Please follow the instructions below to complete your payment:</p>
                    </div>
                    <div class="ins-con">
                        <div class="gcash">
                                <p style="margin-bottom: 3%;"><strong>Gcash:</strong> Ensure that the amount sent for the payment is exactly ₱500. Any amount deviating from this will result in the denial of your transaction. Send your payment by scanning the QR code below:</p>
                                <img src="../images/gcash.jpg" alt="Gcash QR Code" width="220" height="300">
                        </div>
                        <div class="inperson">
                                <p style="margin-bottom: 3%;"><strong>In-person:</strong> Please refer to the Google Maps location provided for the exact address and directions. Make your payment at Sultan Kudarat State University - Kalamansig Campus.</p>
                                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4075.7300686677243!2d124.04936719999999!3d6.5577877999999995!2m3!1f75!2f188.23!3f82.73!3m2!1i8192!2i4096!4f13.1!3m3!1m2!1s0x0%3A0xdhf3yT4bhrOogHNHQ82F0g!2sSample%20Location!5e0!3m2!1sen!2sph!4v1625127550345!5m2!1sen!2sph" width="300" height="300" style="border: 2px solid black;" allowfullscreen="" loading="lazy"></iframe>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form" style="margin: 2%; height:fit-content;">
                <h1>Payment Form</h1>

                <div class="alert-messages">
                    <?php if (!empty($success_msg)): ?>
                        <span class="success-msg"><?php echo $success_msg; ?></span>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <?php foreach ($error as $errorMsg): ?>
                            <span class="error-msg"><?php echo $errorMsg; ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                    <div class="textbox" style="margin-top: 10%;">
                        <select id="payment_mode" name="payment_mode" required style="padding: 2%;">
                            <option value="" disabled selected>Mode of Payment</option>
                            <option value="Gcash" style="color: black;"?php echo isset($payment_mode) && $payment_mode == 'Gcash' ? 'selected' : ''; ?>Gcash</option>
                            <option value="in-person" style="color: black;"<?php echo isset($payment_mode) && $payment_mode == 'in-person' ? 'selected' : ''; ?>>In-person</option>
                        </select>
                    </div>
                    <div class="upload-receipt">
                        <label for="receipt_image">Receipt Image (Gcash):</label>
                        <input style="margin-bottom: 10%; margin-top: 5%;" type="file" id="receipt_image" name="receipt_image">
                        <label style="font-size: 80%">
                            <input type="checkbox" name="agree" required>
                            I agree to make a payment of ₱500.
                        </label>
                    </div>
                    
                    <button class="button" type="submit" name="payment">Register and Confirm Payment</button>
                </form>
            </div>
        </div>

        <footer class="footer">
            &copy; 2023 Boarding House Booking. All rights reserved.
        </footer>
    </body>
</html>