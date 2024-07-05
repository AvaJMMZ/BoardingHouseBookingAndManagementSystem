<?php
    @include '../conn/config.php';
    date_default_timezone_set('Asia/Manila');

    // Set a default value for $pageLimit
    $pageLimit = 10;

    // Pagination variables
    $currentpage = isset($_GET['page']) ? $_GET['page'] : 1;
    $offset = ($currentpage - 1) * $pageLimit;

    // Fetch limited rows based on pagination and search value (if applicable)
    $searchValue = isset($_POST['searchInput']) ? $_POST['searchInput'] : '';

    $queryLimited = "SELECT landlord_id, id, username, status, start_date, end_date, mode_of_payment, receipt, denial_reason FROM landlord_subscription WHERE status = 'expired' OR status = 'denied'";

    if (!empty($searchValue)) {
        $queryLimited .= " AND (landlord_id LIKE '%$searchValue%' OR username LIKE '%$searchValue%')";
    }    

    $queryLimited .= " ORDER BY id DESC LIMIT $offset, $pageLimit";
    $resultLimited = mysqli_query($conn, $queryLimited);

    // Handle database query errors
    if (!$resultLimited) {
        die("Error retrieving limited expired or denied users: " . mysqli_error($conn));
    }

    $totalRows = mysqli_num_rows($resultLimited);
    // Avoid DivisionByZeroError by checking if $pageLimit is not zero
    $totalPages = ($pageLimit !== 0) ? ceil($totalRows / $pageLimit) : 0;

    // Fetch the total count of rows without the LIMIT clause
    $totalQuery = "SELECT COUNT(*) as total FROM landlord_subscription WHERE status = 'expired' OR status = 'denied'";
    if (!empty($searchValue)) {
        $totalQuery .= " AND (landlord_id LIKE '%$searchValue%' OR username LIKE '%$searchValue%')";
    }

    $totalResult = mysqli_query($conn, $totalQuery);

    // Handle database query errors
    if (!$totalResult) {
        die("Error retrieving total row count: " . mysqli_error($conn));
    }

    // Get the total number of rows
    $totalRows = 0;
    if ($row = mysqli_fetch_assoc($totalResult)) {
        $totalRows = $row['total'];
    }

    // Calculate the total number of pages
    $totalPages = ($pageLimit !== 0) ? ceil($totalRows / $pageLimit) : 0;
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Subcription Transactions</title>
        <link rel="stylesheet" type="text/css" href="../Style/style_admin.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="icon" type="image/png" href="../images/icon.png">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                // Search functionality
                $('#searchInput').keyup(function() {
                    var searchValue = $(this).val();
                    $.ajax({
                        url: 'search_landlords.php',
                        type: 'POST',
                        data: { search: searchValue },
                        success: function(response) {
                            $('#resultTable').html(response);
                        }
                    });
                });
            });

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
        
        <h2 class="transaction-title">Transactions:</h2>

        <div class="search">
            <input class="searchInput" type="text" id="searchInput" name="searchInput" placeholder="Search Username or ID">
        </div>

        <div id="resultTable">
            <?php if (mysqli_num_rows($resultLimited) > 0) : ?>
                <div class="main" style="padding: 2%; flex-direction: row; margin-top: 2%; margin-bottom: 2%;">
                    <?php while ($row = mysqli_fetch_assoc($resultLimited)) : ?>
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

                                    <div class="receipt">
                                        <p><strong>Payment:</strong> <?php echo $row['mode_of_payment']; ?></p>
                                        <p><strong>Receipt:</strong>
                                            <?php
                                                if ($row['receipt'] === 'none') {
                                                    echo 'No receipt';
                                                } else {
                                                    echo '<a href="' . $row['receipt'] . '" target="_blank">View</a>';
                                                }
                                            ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($row['status'] === 'denied') : ?>
                                    <div style="display: flex; flex-direction:column; border-right: 3px solid #140C06; border-bottom: 2px solid #140C06;">
                                        <button class="view-denial-btn" style="flex: 1;" onclick="openDenialModal('<?php echo $row['denial_reason']; ?>', '<?php echo date("F j, Y | h:i A", strtotime($row['end_date'])); ?>')">View Denial Details</button>
                                    </div>
                                <?php else : ?>
                                    <div class="sub">
                                        <p class="sub-label">Subscription</p>
                                        <p class="date"><?php echo date("F j, Y h:i A", strtotime($row['start_date'])); ?> &middot; <?php echo date("F j, Y h:i A", strtotime($row['end_date'])); ?> </p>
                                    </div>
                                <?php endif; ?>
                                    

                                <div class="stat-exp">
                                    <p><?php echo $row['status']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <p class="no-user">No Transaction.</p>
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

        <!-- PAGING -->
        <div class="paging">
            <?php if ($totalPages > 1) : ?>
                <div class="pagination">
                    <?php if ($currentpage > 1) : ?>
                        <a class="page-link" href="?page=<?php echo ($currentpage - 1); ?>">&lt; Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                        <?php if ($i == $currentpage) : ?>
                            <span class="current-page"><?php echo $i; ?></span>
                        <?php else : ?>
                            <a class="pages" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($currentpage < $totalPages) : ?>
                        <a class="page-link" href="?page=<?php echo ($currentpage + 1); ?>">Next &gt;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <footer class="footer">
            &copy; 2023 Boarding House Booking. All rights reserved.
        </footer>
    </body>
</html>
