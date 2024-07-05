<?php
    session_start();
    @include '../conn/config.php';
    $errors = array();

    if (isset($_POST['login'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = $_POST['password'];

        // Check if username exists in the database
        $check_username_query = "SELECT * FROM user_info WHERE username = '$username'";
        $check_username_result = mysqli_query($conn, $check_username_query);
        if (mysqli_num_rows($check_username_result) == 0) {
            $errors[] = 'Username does not exist.';
        } else {
            // Verify password
            $user_data = mysqli_fetch_assoc($check_username_result);
            if (!password_verify($password, $user_data['password'])) {
                $errors[] = 'Incorrect password.';
            } else {
                $user_type = $user_data['user_type'];
                
                // Check user type
                if ($user_type == 'landlord') {
                    // Check landlord subscription status
                    $status_query = "SELECT status FROM landlord_subscription WHERE username = '$username' ORDER BY id DESC LIMIT 1";
                    $status_result = mysqli_query($conn, $status_query);
                    if (mysqli_num_rows($status_result) == 0) {
                        $errors[] = 'No subscription found for this username.';
                    } else {
                        $status_row = mysqli_fetch_assoc($status_result);
                        $status = $status_row['status'];
                        
                        if ($status == 'pending') {
                            $errors[] = 'Your account is pending. Please wait.';
                        } elseif ($status == 'denied') {
                            $errors[] = 'Your account has been denied. Please contact the administrator.';
                        } elseif ($status == 'expired') {
                            echo '<script>alert("Your subscription has expired. Please renew your subscription."); 
                                window.location.href = "frm_renew.php";</script>';
                            exit();
                        } elseif ($status == 'pending renew') {
                            $errors[] = 'Your account renewal is pending. Please wait.';
                        } else {
                            if (!empty($user_data)) {
                                // Successful login for landlord
                                $_SESSION['user_type'] = $user_type;
                                $_SESSION['username'] = $username;
                                $_SESSION['email'] = $user_data['email'];
                                $_SESSION['user_id'] = $user_data['id'];
                            
                                header("Location: ../Landlord/pg_landlord.php");
                                exit();
                            }
                        }
                    }
                } else {
                    // Successful login for tenant
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $user_data['email'];
                    $_SESSION['user_id'] = $user_data['id'];
                    
                    header("Location: ../Tenant/pg_tenant.php");
                    exit();
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Login Form</title>
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
                    <h1 class="title-label">Login Form</h1>

                    <div class="alert-messages" style="max-width: 290px; text-align: center;">
                        <?php if (!empty($errors)): ?>
                            <div class="error-container">
                                <?php foreach ($errors as $error): ?>
                                    <span class="error-msg"><?php echo $error; ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="user-input">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="textbox">
                                <i class='fas fa-user-circle'></i>
                                <input type="text" id="username" name="username" required placeholder="Username">
                            </div>
                            <div class="textbox">
                                <i class="fa fa-lock"></i>
                                <input type="password" id="password" name="password" required placeholder="Password">
                                <div class="eye-icon">
                                    <input type="checkbox" id="password-toggle" onclick="togglePasswordVisibility()">
                                    <label for="password-toggle"><i id="password-icon" class="fa fa-eye"></i></label>
                                </div>
                            </div>
                            <div>
                                <button class="button" type="submit" name="login">LOGIN</button>
                            </div>

                            <div class="register-link">
                                <p>Don't have an account? <span onclick="location.href='frm_registration.php';" class="register-button">Register</span></p>
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