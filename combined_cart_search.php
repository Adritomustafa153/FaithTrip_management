<?php
include 'auth_check.php';
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize combined cart
if (!isset($_SESSION['combined_invoice_cart'])) {
    $_SESSION['combined_invoice_cart'] = [];
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $service_type = $_POST['service_type'];
    $record_id = intval($_POST['record_id']);
    
    $cart_item = [
        'service_type' => $service_type,
        'record_id' => $record_id
    ];
    
    // Check if item already exists in cart
    $exists = false;
    foreach ($_SESSION['combined_invoice_cart'] as $item) {
        if ($item['service_type'] == $service_type && $item['record_id'] == $record_id) {
            $exists = true;
            break;
        }
    }
    
    if (!$exists) {
        $_SESSION['combined_invoice_cart'][] = $cart_item;
    }
    
    echo json_encode(['success' => true, 'cart_count' => count($_SESSION['combined_invoice_cart'])]);
    exit;
}

// Handle search
$search_results = [];
$search_query = "";
$party_filter = "";
$section_filter = "";
$service_filter = "";

// Default: Show all records initially
$show_all = true;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['search']) || isset($_GET['party_name']) || isset($_GET['section']) || isset($_GET['service_type']))) {
    $search_query = $_GET['search'] ?? '';
    $party_filter = $_GET['party_name'] ?? '';
    $section_filter = $_GET['section'] ?? '';
    $service_filter = $_GET['service_type'] ?? '';
    $show_all = false;
}

// Build search conditions for each table separately
$airticket_conditions = [];
$hotel_conditions = [];
$visa_conditions = [];
$student_conditions = [];
$umrah_conditions = [];

$airticket_params = [];
$hotel_params = [];
$visa_params = [];
$student_params = [];
$umrah_params = [];

$airticket_types = '';
$hotel_types = '';
$visa_types = '';
$student_types = '';
$umrah_types = '';

if (!empty($search_query)) {
    // Airticket conditions
    $airticket_conditions[] = "(s.PNR LIKE ? OR s.TicketNumber LIKE ? OR s.invoice_number LIKE ?)";
    $search_param = "%$search_query%";
    $airticket_params = [$search_param, $search_param, $search_param];
    $airticket_types = 'sss';
    
    // Hotel conditions
    $hotel_conditions[] = "(h.reference_number LIKE ? OR h.invoice_number LIKE ?)";
    $hotel_params = [$search_param, $search_param];
    $hotel_types = 'ss';
    
    // Visa conditions
    $visa_conditions[] = "(v.visano LIKE ?)";
    $visa_params = [$search_param];
    $visa_types = 's';
    
    // Student conditions - no specific search columns in your structure
    $student_conditions[] = "1=1"; // Placeholder
    
    // Umrah conditions - no specific search columns in your structure
    $umrah_conditions[] = "1=1"; // Placeholder
}

if (!empty($party_filter)) {
    // Airticket conditions
    $airticket_conditions[] = "s.PartyName = ?";
    $airticket_params[] = $party_filter;
    $airticket_types .= 's';
    
    // Hotel conditions
    $hotel_conditions[] = "h.partyName = ?";
    $hotel_params[] = $party_filter;
    $hotel_types .= 's';
    
    // Visa conditions
    $visa_conditions[] = "v.`party name` = ?";
    $visa_params[] = $party_filter;
    $visa_types .= 's';
    
    // Student conditions
    $student_conditions[] = "st.`party name` = ?";
    $student_params[] = $party_filter;
    $student_types .= 's';
    
    // Umrah conditions
    $umrah_conditions[] = "u.`party name` = ?";
    $umrah_params[] = $party_filter;
    $umrah_types .= 's';
}

if (!empty($section_filter)) {
    // Airticket conditions
    $airticket_conditions[] = "s.Section = ?";
    $airticket_params[] = $section_filter;
    $airticket_types .= 's';
    
    // Hotel conditions - no section column in your hotel table
    $hotel_conditions[] = "1=1"; // Placeholder
}

// Build WHERE clauses
$airticket_where = !empty($airticket_conditions) ? "WHERE " . implode(" AND ", $airticket_conditions) : "";
$hotel_where = !empty($hotel_conditions) ? "WHERE " . implode(" AND ", $hotel_conditions) : "";
$visa_where = !empty($visa_conditions) ? "WHERE " . implode(" AND ", $visa_conditions) : "";
$student_where = !empty($student_conditions) ? "WHERE " . implode(" AND ", $student_conditions) : "";
$umrah_where = !empty($umrah_conditions) ? "WHERE " . implode(" AND ", $umrah_conditions) : "";

// Execute queries based on service filter or show all
$search_results = [];

// Airticket Query
if ($show_all || empty($service_filter) || $service_filter == 'ticket') {
    $airticket_query = "SELECT 
        'airticket' as service_type,
        s.SaleID as record_id,
        s.PNR,
        s.TicketNumber as booking_reference,
        s.invoice_number as Invoice_number,
        s.PartyName,
        s.PassengerName,
        s.TicketRoute,
        s.airlines,
        s.FlightDate,
        s.ReturnDate,
        s.IssueDate,
        s.Class,
        s.BillAmount as amount,
        s.Remarks,
        s.Section,
        NULL as hotelName,
        NULL as checkin_date,
        NULL as checkout_date
    FROM sales s 
    {$airticket_where}";

    if (!empty($airticket_params)) {
        $stmt = $conn->prepare($airticket_query);
        if ($stmt) {
            $stmt->bind_param($airticket_types, ...$airticket_params);
            $stmt->execute();
            $airticket_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $search_results = array_merge($search_results, $airticket_results);
            $stmt->close();
        }
    } else {
        $airticket_results = $conn->query($airticket_query);
        if ($airticket_results) {
            $search_results = array_merge($search_results, $airticket_results->fetch_all(MYSQLI_ASSOC));
        }
    }
}

// Hotel Query - Fixed with correct column names from your hotel table
if ($show_all || empty($service_filter) || $service_filter == 'hotel') {
    $hotel_query = "SELECT 
        'hotel' as service_type,
        h.id as record_id,
        NULL as PNR,
        h.reference_number as booking_reference,
        h.invoice_number as Invoice_number,
        h.partyName as PartyName,
        h.pessengerName as PassengerName,
        CONCAT(h.hotelName, ' - ', h.country) as TicketRoute,
        NULL as airlines,
        h.checkin_date as FlightDate,
        h.checkout_date as ReturnDate,
        h.issue_date as IssueDate,
        CONCAT(h.room_type, ' - ', h.room_category) as Class,
        h.selling_price as amount,
        NULL as Remarks,
        NULL as Section,
        h.hotelName,
        h.checkin_date,
        h.checkout_date
    FROM hotel h 
    {$hotel_where}";

    if (!empty($hotel_params)) {
        $stmt = $conn->prepare($hotel_query);
        if ($stmt) {
            $stmt->bind_param($hotel_types, ...$hotel_params);
            $stmt->execute();
            $hotel_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $search_results = array_merge($search_results, $hotel_results);
            $stmt->close();
        }
    } else {
        $hotel_results = $conn->query($hotel_query);
        if ($hotel_results) {
            $search_results = array_merge($search_results, $hotel_results->fetch_all(MYSQLI_ASSOC));
        }
    }
}

// Visa Query
if ($show_all || empty($service_filter) || $service_filter == 'visa') {
    $visa_query = "SELECT 
        'visa' as service_type,
        v.id as record_id,
        NULL as PNR,
        v.visano as booking_reference,
        NULL as Invoice_number,
        v.`party name` as PartyName,
        v.name as PassengerName,
        CONCAT(v.country, ' - ', v.Type) as TicketRoute,
        NULL as airlines,
        NULL as FlightDate,
        NULL as ReturnDate,
        v.orderdate as IssueDate,
        CONCAT('Duration: ', v.Duration) as Class,
        v.`selling price` as amount,
        NULL as Remarks,
        NULL as Section,
        NULL as hotelName,
        NULL as checkin_date,
        NULL as checkout_date
    FROM visa v 
    {$visa_where}";

    if (!empty($visa_params)) {
        $stmt = $conn->prepare($visa_query);
        if ($stmt) {
            $stmt->bind_param($visa_types, ...$visa_params);
            $stmt->execute();
            $visa_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $search_results = array_merge($search_results, $visa_results);
            $stmt->close();
        }
    } else {
        $visa_results = $conn->query($visa_query);
        if ($visa_results) {
            $search_results = array_merge($search_results, $visa_results->fetch_all(MYSQLI_ASSOC));
        }
    }
}

// Student Query
if ($show_all || empty($service_filter) || $service_filter == 'student') {
    $student_query = "SELECT 
        'student' as service_type,
        st.id as record_id,
        NULL as PNR,
        NULL as booking_reference,
        NULL as Invoice_number,
        st.`party name` as PartyName,
        st.`student name` as PassengerName,
        CONCAT(st.country, ' - ', st.`University name`) as TicketRoute,
        NULL as airlines,
        NULL as FlightDate,
        NULL as ReturnDate,
        st.`received date` as IssueDate,
        NULL as Class,
        st.Selling as amount,
        NULL as Remarks,
        NULL as Section,
        NULL as hotelName,
        NULL as checkin_date,
        NULL as checkout_date
    FROM student st 
    {$student_where}";

    if (!empty($student_params)) {
        $stmt = $conn->prepare($student_query);
        if ($stmt) {
            $stmt->bind_param($student_types, ...$student_params);
            $stmt->execute();
            $student_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $search_results = array_merge($search_results, $student_results);
            $stmt->close();
        }
    } else {
        $student_results = $conn->query($student_query);
        if ($student_results) {
            $search_results = array_merge($search_results, $student_results->fetch_all(MYSQLI_ASSOC));
        }
    }
}

// Umrah Query
if ($show_all || empty($service_filter) || $service_filter == 'umrah') {
    $umrah_query = "SELECT 
        'umrah' as service_type,
        u.id as record_id,
        NULL as PNR,
        NULL as booking_reference,
        NULL as Invoice_number,
        u.`party name` as PartyName,
        u.`pax name` as PassengerName,
        'Umrah Package' as TicketRoute,
        NULL as airlines,
        u.dateoftravel as FlightDate,
        NULL as ReturnDate,
        u.orderdate as IssueDate,
        NULL as Class,
        u.`selling price` as amount,
        NULL as Remarks,
        NULL as Section,
        NULL as hotelName,
        NULL as checkin_date,
        NULL as checkout_date
    FROM umrah u 
    {$umrah_where}";

    if (!empty($umrah_params)) {
        $stmt = $conn->prepare($umrah_query);
        if ($stmt) {
            $stmt->bind_param($umrah_types, ...$umrah_params);
            $stmt->execute();
            $umrah_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $search_results = array_merge($search_results, $umrah_results);
            $stmt->close();
        }
    } else {
        $umrah_results = $conn->query($umrah_query);
        if ($umrah_results) {
            $search_results = array_merge($search_results, $umrah_results->fetch_all(MYSQLI_ASSOC));
        }
    }
}

// Fetch party names for dropdown
$party_names = [];
$party_query = "SELECT DISTINCT PartyName FROM sales WHERE PartyName IS NOT NULL AND PartyName != ''
                UNION 
                SELECT DISTINCT partyName FROM hotel WHERE partyName IS NOT NULL AND partyName != ''
                UNION 
                SELECT DISTINCT `party name` FROM visa WHERE `party name` IS NOT NULL AND `party name` != ''
                UNION 
                SELECT DISTINCT `party name` FROM umrah WHERE `party name` IS NOT NULL AND `party name` != ''
                UNION 
                SELECT DISTINCT `party name` FROM student WHERE `party name` IS NOT NULL AND `party name` != ''";
$party_result = $conn->query($party_query);
while ($row = $party_result->fetch_assoc()) {
    $party_names[] = $row['PartyName'] ?? $row['partyName'] ?? $row['party name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Combined Invoice Cart - Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .service-badge {
            font-size: 0.7em;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .airticket-badge { background: #007bff; color: white; }
        .hotel-badge { background: #28a745; color: white; }
        .visa-badge { background: #ffc107; color: black; }
        .umrah-badge { background: #dc3545; color: white; }
        .student-badge { background: #6f42c1; color: white; }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .search-loading {
            display: none;
            text-align: center;
            padding: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Combined Invoice Cart</h2>
            <div class="position-relative">
                <a href="combined_invoice_cart.php" class="btn btn-primary position-relative">
                    <i class="fas fa-shopping-cart"></i> View Cart
                    <?php if (!empty($_SESSION['combined_invoice_cart'])): ?>
                        <span class="cart-count"><?php echo count($_SESSION['combined_invoice_cart']); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <!-- Search Form -->
        <div class="search-form">
            <form id="searchForm" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Service Type</label>
                        <select class="form-select" name="service_type" id="serviceType">
                            <option value="">All Services</option>
                            <option value="ticket" <?php echo ($service_filter == 'ticket') ? 'selected' : ''; ?>>Air Ticket</option>
                            <option value="hotel" <?php echo ($service_filter == 'hotel') ? 'selected' : ''; ?>>Hotel</option>
                            <option value="visa" <?php echo ($service_filter == 'visa') ? 'selected' : ''; ?>>Visa</option>
                            <option value="student" <?php echo ($service_filter == 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="umrah" <?php echo ($service_filter == 'umrah') ? 'selected' : ''; ?>>Umrah</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Search (PNR/Booking ID/Invoice No)</label>
                        <input type="text" class="form-control" name="search" id="searchInput" 
                               value="<?php echo htmlspecialchars($search_query); ?>" 
                               placeholder="Search by PNR, Booking ID, or Invoice No...">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Party Name</label>
                        <select class="form-select" name="party_name" id="partyName">
                            <option value="">All Parties</option>
                            <?php foreach ($party_names as $party): ?>
                                <option value="<?php echo htmlspecialchars($party); ?>" 
                                    <?php echo ($party_filter == $party) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($party); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Section</label>
                        <select class="form-select" name="section" id="section">
                            <option value="">All Sections</option>
                            <option value="company" <?php echo ($section_filter == 'company') ? 'selected' : ''; ?>>Company</option>
                            <option value="agent" <?php echo ($section_filter == 'agent') ? 'selected' : ''; ?>>Agent</option>
                            <option value="counter" <?php echo ($section_filter == 'counter') ? 'selected' : ''; ?>>Counter Sell</option>
                            <option value="passenger" <?php echo ($section_filter == 'passenger') ? 'selected' : ''; ?>>Passenger</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Loading Indicator -->
        <div id="searchLoading" class="search-loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Searching...</p>
        </div>

        <!-- Search Results -->
        <div id="searchResults">
            <?php if (!empty($search_results)): ?>
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">Search Results (<?php echo count($search_results); ?> found)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">Service</th>
                                        <th width="10%">Reference</th>
                                        <th width="15%">Party Name</th>
                                        <th width="15%">Passenger/Customer</th>
                                        <th width="20%">Details</th>
                                        <th width="10%">Dates</th>
                                        <th width="10%">Amount</th>
                                        <th width="15%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($search_results as $row): ?>
                                        <tr>
                                            <td>
                                                <span class="service-badge <?php echo $row['service_type']; ?>-badge">
                                                    <?php echo strtoupper($row['service_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php if ($row['service_type'] == 'airticket'): ?>
                                                        <strong>PNR:</strong> <?php echo $row['PNR'] ?? 'N/A'; ?><br>
                                                    <?php elseif ($row['service_type'] == 'hotel'): ?>
                                                        <strong>Ref:</strong> <?php echo $row['booking_reference'] ?? 'N/A'; ?><br>
                                                    <?php elseif ($row['service_type'] == 'visa'): ?>
                                                        <strong>Visa No:</strong> <?php echo $row['booking_reference'] ?? 'N/A'; ?><br>
                                                    <?php endif; ?>
                                                    <?php if ($row['Invoice_number']): ?>
                                                        <strong>Inv:</strong> <?php echo $row['Invoice_number']; ?>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['PartyName'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['PassengerName'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if ($row['service_type'] == 'airticket'): ?>
                                                    <small>
                                                        <strong>Route:</strong> <?php echo htmlspecialchars($row['TicketRoute']); ?><br>
                                                        <strong>Airline:</strong> <?php echo htmlspecialchars($row['airlines']); ?>
                                                    </small>
                                                <?php elseif ($row['service_type'] == 'hotel'): ?>
                                                    <small>
                                                        <strong>Hotel:</strong> <?php echo htmlspecialchars($row['hotelName']); ?><br>
                                                        <strong>Room:</strong> <?php echo htmlspecialchars($row['Class']); ?>
                                                    </small>
                                                <?php elseif ($row['service_type'] == 'visa'): ?>
                                                    <small>
                                                        <strong>Type:</strong> <?php echo htmlspecialchars($row['TicketRoute']); ?>
                                                    </small>
                                                <?php elseif ($row['service_type'] == 'student'): ?>
                                                    <small>
                                                        <strong>University:</strong> <?php echo htmlspecialchars($row['TicketRoute']); ?>
                                                    </small>
                                                <?php elseif ($row['service_type'] == 'umrah'): ?>
                                                    <small>
                                                        <strong>Package:</strong> Umrah
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php if ($row['service_type'] == 'airticket'): ?>
                                                        <strong>Depart:</strong> <?php echo $row['FlightDate']; ?><br>
                                                        <?php if ($row['ReturnDate'] && $row['ReturnDate'] != '0000-00-00'): ?>
                                                            <strong>Return:</strong> <?php echo $row['ReturnDate']; ?>
                                                        <?php endif; ?>
                                                    <?php elseif ($row['service_type'] == 'hotel'): ?>
                                                        <strong>Check-in:</strong> <?php echo $row['checkin_date']; ?><br>
                                                        <strong>Check-out:</strong> <?php echo $row['checkout_date']; ?>
                                                    <?php elseif ($row['service_type'] == 'visa'): ?>
                                                        <strong>Order:</strong> <?php echo $row['IssueDate']; ?>
                                                    <?php elseif ($row['service_type'] == 'student'): ?>
                                                        <strong>Received:</strong> <?php echo $row['IssueDate']; ?>
                                                    <?php elseif ($row['service_type'] == 'umrah'): ?>
                                                        <strong>Travel:</strong> <?php echo $row['FlightDate']; ?>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong>৳<?php echo number_format($row['amount'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <button class="btn btn-success btn-sm add-to-cart-btn" 
                                                        data-service-type="<?php echo $row['service_type']; ?>"
                                                        data-record-id="<?php echo $row['record_id']; ?>">
                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php elseif (!$show_all): ?>
                <div class="alert alert-info mt-3">
                    No records found matching your search criteria.
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-3">
                    Use the search form above to find sales records to add to your invoice cart.
                    <?php if ($show_all): ?>
                        <br><strong>Currently showing all available records.</strong>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add to cart functionality
        function attachAddToCartListeners() {
            document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const serviceType = this.getAttribute('data-service-type');
                    const recordId = this.getAttribute('data-record-id');
                    const button = this;
                    
                    // Disable button and show loading
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                    
                    fetch('combined_cart_search.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'add_to_cart=1&service_type=' + serviceType + '&record_id=' + recordId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            button.innerHTML = '<i class="fas fa-check"></i> Added';
                            button.classList.remove('btn-success');
                            button.classList.add('btn-secondary');
                            
                            // Update cart count
                            updateCartCount(data.cart_count);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                        button.disabled = false;
                    });
                });
            });
        }

        // Update cart count
        function updateCartCount(count) {
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = count;
            } else {
                // Create cart count if it doesn't exist
                const cartLink = document.querySelector('a[href="combined_invoice_cart.php"]');
                if (cartLink) {
                    const newCount = document.createElement('span');
                    newCount.className = 'cart-count';
                    newCount.textContent = count;
                    cartLink.appendChild(newCount);
                }
            }
        }

        // Real-time search functionality
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const serviceType = document.getElementById('serviceType');
        const partyName = document.getElementById('partyName');
        const section = document.getElementById('section');
        const searchForm = document.getElementById('searchForm');
        const searchResults = document.getElementById('searchResults');
        const searchLoading = document.getElementById('searchLoading');

        function performSearch() {
            searchLoading.style.display = 'block';
            searchResults.style.display = 'none';
            
            const formData = new FormData(searchForm);
            const params = new URLSearchParams(formData);
            
            fetch('combined_cart_search.php?' + params.toString())
                .then(response => response.text())
                .then(html => {
                    // Extract the search results from the response
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    const newResults = tempDiv.querySelector('#searchResults');
                    
                    if (newResults) {
                        searchResults.innerHTML = newResults.innerHTML;
                        // Re-attach event listeners to new buttons
                        attachAddToCartListeners();
                    }
                    
                    searchLoading.style.display = 'none';
                    searchResults.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    searchLoading.style.display = 'none';
                    searchResults.style.display = 'block';
                });
        }

        // Real-time search on input
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch();
            }, 500);
        });

        // Auto-search on dropdown changes
        serviceType.addEventListener('change', performSearch);
        partyName.addEventListener('change', performSearch);
        section.addEventListener('change', performSearch);

        // Initial attachment of event listeners
        attachAddToCartListeners();
    </script>
</body>
</html>