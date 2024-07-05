<?php
    @include '../conn/config.php';

    if (isset($_POST['search'])) {
        $searchValue = $_POST['search'];
        // Convert the search value to lowercase
        $searchValue = strtolower($_POST['search']);

        // Fetch expired landlords based on the search value
        $query = "SELECT landlord_id, id, username, status, start_date, end_date, mode_of_payment, receipt, denial_reason FROM landlord_subscription WHERE (status = 'expired' OR status = 'denied') AND (LOWER(landlord_id) LIKE '$searchValue%' OR LOWER(username) LIKE '$searchValue%') ORDER BY id DESC";
        $result = mysqli_query($conn, $query);


        // Handle database query errors
        if (!$result) {
            die("Error retrieving expired users: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($result) > 0) {
            echo '<div class="main" style="padding: 2%; flex-direction: row; margin-top: 2%; margin-bottom: 2%;">';
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<div class="card">';
                echo '<div class="tran">';
                echo '<div class="label">Transaction No.</div>';
                echo '<div class="num">' . $row['id'] . '</div>';
                echo '</div>';
                echo '<div class="details">';
                echo '<div class="info">';
                echo '<div class="name-id">';
                echo '<p class="username">' . $row['username'] . '</p>';
                echo '<p class="id">ID: ' . $row['landlord_id'] . '</p>';
                echo '</div>';
                echo '<div class="receipt">';
                echo '<p><strong>Payment: </strong> ' . $row['mode_of_payment'] . '</p>';
                echo '<p><strong>Receipt: </strong>';
                if ($row['receipt'] === 'none') {
                    echo 'No receipt';
                } else {
                    echo '<a href="' . $row['receipt'] . '" target="_blank">View</a>';
                }
                echo '</p>';
                echo '</div>';
                echo '</div>';
                
                if ($row['status'] === 'denied') {
                    echo '<div style="display: flex; flex-direction:column; border-right: 3px solid #140C06; border-bottom: 2px solid #140C06;">';
                    echo '<button class="view-denial-btn" style="flex: 1;" onclick="openDenialModal(\''. $row['denial_reason'] . '\', \'' . date("F j, Y | h:i A", strtotime($row['end_date'])) . '\')">View Denial Details</button>';
                    echo '</div>';
                } else {
                    echo '<div class="sub">';
                    echo '<p class="sub-label">Subscription</p>';
                    echo '<p class="date">' . date("F j, Y h:i A", strtotime($row['start_date'])) . ' &middot; ' . date("F j, Y h:i A", strtotime($row['end_date'])) . '</p>';
                    echo '</div>';
                }
                
                echo '<div class="stat-exp">';
                echo '<p>' . $row['status'] . '</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p class="no-user">User not found.</p>';
        }
    }
?>
