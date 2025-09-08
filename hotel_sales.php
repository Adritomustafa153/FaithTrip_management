<?php
include 'db.php';
include 'auth_check.php';

// Fetch company names for dropdown
$companyQuery = "SELECT DISTINCT partyName FROM hotel";
$companyResult = $conn->query($companyQuery);

// Fetch sales records
$where = "";
if (isset($_GET['company']) && !empty($_GET['company'])) {
    $company = $conn->real_escape_string($_GET['company']);
    $where .= " WHERE partyName = '$company'";
}
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $invoice = $conn->real_escape_string($_GET['invoice']);
    $where .= ($where ? " AND" : " WHERE") . " invoice_number LIKE '%$invoice%'";
}
if (isset($_GET['booking_id']) && !empty($_GET['booking_id'])) {
    $pnr_ = $conn->real_escape_string($_GET['booking_id']);
    $where .= ($where ? " AND" : " WHERE") . " reference_number LIKE '%$pnr_%'";
}

$salesQuery = "SELECT * FROM hotel" . $where;
$salesResult = $conn->query($salesQuery);

// Delete record
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM hotel WHERE id=$id";
    if ($conn->query($deleteQuery) === TRUE) {
        echo "<script>alert('Record deleted successfully!'); window.location='hotel_sales.php';</script>";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Records</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; border-radius: 20px;border-collapse: collapse;box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);}
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; border-radius: 20px;}
        th { background-color:rgb(74, 113, 255); color: white; border-radius: 5px; }
        .search-container { display: flex; gap: 10px; margin-bottom: 20px; border-radius: 15px;}
        .search-container select, .search-container input { padding: 8px; width: 200px; }
        .btn { padding: 5px 10px; border: none; cursor: pointer; text-decoration: none; font-size: 12px; padding: 4px 8px }
        .edit-btn { background-color:rgb(7, 147, 32); color: white; }
        .delete-btn { background-color: #d9534f; color: white; }
        .btn:hover { opacity: 0.8; }

    </style>

</head>
<body>

 <!-- Start your project here-->
<!-- Navbar -->
<?php include 'nav.php' ?>
<!-- Navbar -->


<h2 style="margin-top: 10px; text-align:center">Sales Records</h2>
<div style="display:flex; justify-content: center;">
<!-- Search Form -->
<form method="GET" class="search-container" >
    <select name="company">
        <option value="">Select All</option>
        <?php while ($row = $companyResult->fetch_assoc()) : ?>
            <option value="<?= htmlspecialchars($row['partyName']) ?>" 
                <?= (isset($_GET['company']) && $_GET['company'] == $row['partyName']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['partyName']) ?>
            </option>
        <?php endwhile; ?>
    </select>
    
    <input type="text" name="invoice" placeholder="Search Invoice Number" 
        value="<?= isset($_GET['invoice']) ? htmlspecialchars($_GET['invoice']) : '' ?>">

    <input type="text" name="booking_id" placeholder="Search Booking ID" 
        value="<?= isset($_GET['booking_id']) ? htmlspecialchars($_GET['booking_id']) : '' ?>">
    <button type="submit">Search</button><br>
    <button type="button" onclick="location.href='add_sales_corporate.php'">Add Sales (Corporate)</button>
    <button type="button" onclick="location.href='add_hotel_sales_agents.php'">Add Sales (Agents)</button>
    <button type="button" onclick="location.href='add_hotel_sales_counter.php'">Add Sales (Counter Sales)</button>
</form>

</div>
<!-- Sales Records Table -->
 <div class="result">
<table>
    <tr>
        <th style="font-size: 12px;">Company Name</th>
        <th style="font-size: 12px;">Hotel Name</th>
        <th style="font-size: 12px;">Invoice Number</th>
        <th style="font-size: 12px;">Pessenger Number</th>
        <th style="font-size: 12px;">Check-In Date</th>
        <th style="font-size: 12px;">Check-Out Date</th>
        <th style="font-size: 12px;">Issue Date</th>
        <th style="font-size: 12px;">Day Passes</th>
        <th style="font-size: 12px;">Payment Status</th>
        <th style="font-size: 12px;">Selling Price</th>
        <th style="font-size: 12px;">Sells Person</th>
        <th style="font-size: 12px;">Modify</th>
    </tr>
    <?php while ($row = $salesResult->fetch_assoc()) : 
        $issue_date = new DateTime($row['issue_date']);
        $today = new DateTime();
        $interval = $issue_date->diff($today);
        $day_passes = $interval->days;
        ?>
        <tr>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['partyName']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['hotelName']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['invoice_number']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['pessengerName']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['checkin_date']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['checkout_date']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['issue_date']) ?></td>
            <td style="font-size: 12px;"><?= $day_passes ?> days</td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['payment_status']) ?></td>
            <td style="font-size: 12px;">BDT<?= number_format($row['selling_price'], 2) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['issued_by']) ?></td>
            <td>
    <?php if (isset($row['id'])): ?>
        <a href="edit.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn edit-btn" >
            <i class="fas fa-edit"></i> Edit
        </a><br>
        <a href="hotel_sales.php?delete=<?php echo htmlspecialchars($row['id']); ?>" class="btn delete-btn" 
           onclick="return confirm('Are you sure you want to delete this record?')">
            <i class="fas fa-trash"></i> Delete
            
        </a>
    <?php else: ?>
        <span style="color: red;">Error: No ID Found</span>
    <?php endif; ?>
</td>

        </tr>
    <?php endwhile; ?>
</table>
</body>
<script src="invoice_list.js"></script>

</html>

<?php $conn->close(); ?>
