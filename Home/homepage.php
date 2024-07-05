<!DOCTYPE html>
<html>
<head>
    <title>Boarding House Booking and Management System</title>
    <link rel="stylesheet" type="text/css" href="Style/style_homepage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="icon" type="image/png" href="images/icon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <main>
        <nav>
            <div class="logo">BH for HOME</div> 
            
            <a href="#" class="admin-link" id="admin-link">Admin <i class='fas fa-user-alt'></i></a>
            <div id="password-modal" class="hidden">
                <div id="password-modal-content">
                    <span class="close">&times;</span>
                    <h2>Enter Password</h2>
                    <div class="pass">
                        <input type="password" id="password" placeholder="Enter password">
                        <button id="password-toggle"><i class="fa fa-eye-slash"></i></button>
                    </div>
                    <button id="password-submit" style="width: fit-content; font-weight:bold">Login</button>
                </div>
            </div>
        </nav>
        
        <div class="main">
            <div class="con1">
            <div class="line">
                <h1>YOUR IDEAL <span class="highlight">BOARDING HOUSE</span> AWAITS,
                    <br> <span class="highlight">BOOK NOW</span> AND <br/>
                    <span class="highlight">SETTLE IN</span>.</h1>
                <p>Boarding House Booking and Management System</p>
            </div>
            </div>

            <div class="con2">
            <section class="action">
                <h2>Ready to get started?</h2>
                <a href="./Registration/frm_registration.php" class="button">Register</a>
                <a href="./Registration/frm_login.php" class="button">Login</a>
            </section>
            </div>
        </div>
    </main>

    <footer class="footer">
        &copy; 2023 Boarding House Booking. All rights reserved.
    </footer>

    <script src="../Home/adminlink.js" defer></script>
</body>
</html>
