<?php
    session_start();
    @include '../conn/config.php';
    $error = array();

    if (isset($_POST['register'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];
        $user_type = $_POST['type'];

        // Check if username already exists
        $check_username_query = "SELECT * FROM user_info WHERE username = '$username'";
        $check_username_result = mysqli_query($conn, $check_username_query);
        if (mysqli_num_rows($check_username_result) > 0) {
            $error[] = 'Username already exists.';
        }

        // Check if email already exists
        $check_email_query = "SELECT * FROM user_info WHERE email = '$email'";
        $check_email_result = mysqli_query($conn, $check_email_query);
        if (mysqli_num_rows($check_email_result) > 0) {
            $error[] = 'Email already exists.';
        }

        if (empty($error)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            if ($user_type == 'landlord') {
                $_SESSION['registration_data'] = array(
                    'username' => $username,
                    'email' => $email,
                    'password' => $hashed_password,
                    'user_type' => $user_type
                );

                header("Location: frm_payment.php");
                exit();
            } else {
                $insert = "INSERT INTO user_info (username, email, password, user_type) VALUES ('$username', '$email', '$hashed_password', '$user_type')";
                if (mysqli_query($conn, $insert)) {
                    $success_msg = "Registered successfully! Please Login.";
                    // Clear registration data from session
                    unset($_SESSION['registration_data']);
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Registration Form</title>
        <link rel="stylesheet" type="text/css" href="../Style/style_form.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script>
            <?php if (!empty($success_msg)): ?>
                setTimeout(function() {
                    alert("<?php echo $success_msg; ?>");
                    window.location.href = "frm_login.php";
                }, 0);
            <?php endif; ?>

            setTimeout(function() {
                var errorMessages = document.getElementsByClassName('error-msg');
                while (errorMessages[0]) {
                    errorMessages[0].parentNode.removeChild(errorMessages[0]);
                }
            }, 10000);

            function togglePasswordVisibility() {
                var passwordInput = document.getElementById("password");
                var passwordIcon = document.getElementById("password-icon");
                var isVisible = passwordInput.type === "text";

                if (!isVisible) {
                    passwordInput.type = "text";
                    passwordIcon.classList.remove("fa-eye");
                    passwordIcon.classList.add("fa-eye-slash");
                } else {
                    passwordInput.type = "password";
                    passwordIcon.classList.remove("fa-eye-slash");
                    passwordIcon.classList.add("fa-eye");
                }
            }
        </script>
    </head>
    <body>
        <nav>
            <div class="logo">BH for HOME</div>
            <div class="home">
                <a href="../homepage.php"> <i class="fa fa-home"></i> </a>
            </div>
        </nav>
        <div class="main">
            <div class="form-container">
                <div class="form">
                    <h1 class="title-label">Registration Form</h1>

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

                    <div class="user-input">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="textbox">
                                <i class='fas fa-user-circle'></i>
                                <input type="text" id="username" name="username" required placeholder="Username">
                            </div>
                            <div class="textbox">
                                <i class="fa fa-envelope"></i>
                                <input type="email" id="email" name="email" required placeholder="Email">
                            </div>
                            <div class="textbox">
                                <i class="fa fa-lock"></i>
                                <input type="password" id="password" name="password" required placeholder="Password">
                                <div class="eye-icon">
                                    <input type="checkbox" id="password-toggle" onclick="togglePasswordVisibility()">
                                    <label for="password-toggle"><i id="password-icon" class="fa fa-eye"></i></label>
                                </div>
                            </div>
                            <div style="display: flex;" class="textbox">
                                <select id="type" name="type" required>
                                    <option value="" disabled selected>Select Type</option>
                                    <option value="tenant" <?php echo isset($user_type) && $user_type == 'tenant' ? 'selected' : ''; ?>>Tenant</option>
                                    <option value="landlord" <?php echo isset($user_type) && $user_type == 'landlord' ? 'selected' : ''; ?>>Landlord</option>
                                </select>
                            </div>
                            <div>
                                <button class="button" type="submit" name="register">REGISTER</button>
                            </div>

                            <div class="login-link">
                                <p>Already have an account? <span onclick="location.href='frm_login.php';" class="login-button">Login</span></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer">
            &copy; 2023 Boarding House Booking. All rights reserved.
        </footer>
    </body>
</html>