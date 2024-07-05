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
    $userId = $_SESSION['user_id'];

    // Check if the landlord has already set up their account
    $checkSetupQuery = "SELECT landlord_id FROM owner_about WHERE landlord_id = ?";
    $stmt = mysqli_prepare($conn, $checkSetupQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        // Account setup already done, redirect back to the landlord page
        mysqli_stmt_close($stmt);
        header("Location: pg_landlord.php");
        exit();
    }

    mysqli_stmt_close($stmt);

    // Process the form submission if data is received
    if (isset($_POST['firstname']) && isset($_POST['lastname']) && isset($_POST['contact_no']) && isset($_POST['gender']) && isset($_POST['address']) && isset($_POST['birthdate'])) {
        // Get the input data from the form
        $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
        $middlename = isset($_POST['middlename']) ? mysqli_real_escape_string($conn, $_POST['middlename']) : '';
        $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
        $contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']);
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $birthdate = mysqli_real_escape_string($conn, $_POST['birthdate']);

        // Process the landlord image if uploaded
        if (isset($_FILES['landlord_image']) && $_FILES['landlord_image']['error'] === UPLOAD_ERR_OK) {
            $image_name = $_FILES['landlord_image']['name'];
            $image_tmp = $_FILES['landlord_image']['tmp_name'];
            $image_path = "ProfilePhotos/" . $image_name;
            
            // Move the uploaded image to the desired location
            move_uploaded_file($image_tmp, $image_path);
        } else {
            $image_path = '';
        }

        // Insert the data into the owner_about table
        $insertQuery = "INSERT INTO owner_about (landlord_id, firstname, middlename, lastname, contact_no, gender, address, birthdate, landlord_image) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($stmt, "issssssss", $userId, $firstname, $middlename, $lastname, $contact_no, $gender, $address, $birthdate, $image_path);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Redirect back to the landlord page after successful setup
        header("Location: pg_landlord.php");
        exit();
    } else {
        // If the form data is not received, redirect back to the account setup page
        header("Location: pg_landlord.php");
        exit();
    }
?>
