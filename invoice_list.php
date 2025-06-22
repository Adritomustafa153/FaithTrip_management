<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch company names for dropdown
$companyQuery = "SELECT DISTINCT PartyName FROM sales";
$companyResult = $conn->query($companyQuery);

// Fetch sales records
$where = "";
if (isset($_GET['company']) && !empty($_GET['company'])) {
    $company = $conn->real_escape_string($_GET['company']);
    $where .= " WHERE PartyName = '$company'";
}
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $invoice = $conn->real_escape_string($_GET['invoice']);
    $where .= ($where ? " AND" : " WHERE") . " invoice_number LIKE '%$invoice%'";
}
if (isset($_GET['pnr']) && !empty($_GET['pnr'])) {
    $pnr_ = $conn->real_escape_string($_GET['pnr']);
    $where .= ($where ? " AND" : " WHERE") . " PNR LIKE '%$pnr_%'";
}
if (!empty($where)) {
    $where .= " AND Remarks = 'Sell'";
} else {
    $where = " WHERE Remarks = 'Sell'";
}

$salesQuery = "SELECT * FROM sales" . $where;
$salesResult = $conn->query($salesQuery);

// Delete record
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM sales WHERE SaleID=$id";
    if ($conn->query($deleteQuery) === TRUE) {
        echo "<script>alert('Record deleted successfully!'); window.location='invoice_list.php';</script>";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
        <link rel="icon" href="logo.jpg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png">
    <title>Sales Records</title>
    <!-- <style>
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
        .hr-lines:before{
         content:" ";
         display: block;
         height: 2px;
         width: 100%;
         position: absolute;
         top: 50%;
         left: 0;
         background: grey;
}

    </style> -->
    <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 20px; 
        border-radius: 20px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
    }
    th, td { 
        padding: 10px; 
        border-radius: 5px;
        text-align: left; 
    }
    th { 
        background-color: rgb(74, 113, 255); 
        color: white; 
    }
    .search-container { 
        display: flex; 
        gap: 10px; 
        margin-bottom: 20px; 
        border-radius: 15px;
    }
    .search-container select, .search-container input { 
        padding: 8px; 
        width: 200px; 
    }
    .btn { 
        padding: 5px 10px; 
        border: none; 
        cursor: pointer; 
        text-decoration: none; 
        font-size: 12px; 
        padding: 4px 8px 
    }
    .edit-btn { background-color: rgb(7, 147, 32); color: white; }
    .delete-btn { background-color: #d9534f; color: white; }
    .btn:hover { opacity: 0.8; }

    /* Alternating row colors */
    tr:nth-child(odd) {
        background-color:rgb(238, 241, 255); /* Light grey */
    }
    tr:nth-child(even) {
        background-color: #ffffff; /* White */
    }

    /* Soft line separator between rows */
    tr {
        border-bottom: 1px solid #ddd;
    }

    tr:last-child {
        border-bottom: none;
    }

</style>
</head>
<script>
  document.addEventListener("DOMContentLoaded", function () {
      let cart = JSON.parse(localStorage.getItem("cart")) || [];

      function updateCartCount() {
          document.getElementById("cart-count").textContent = cart.length;
      }

      function addToCart(item) {
          cart.push(item);
          localStorage.setItem("cart", JSON.stringify(cart));
          updateCartCount();
      }

      document.querySelectorAll(".add-to-cart").forEach(button => {
          button.addEventListener("click", function () {
              let passenger = {
                  name: this.dataset.name,
                  price: this.dataset.price,
                  route: this.dataset.route,
                  journey: this.dataset.journey,
                  return: this.dataset.return,
                  pnr: this.dataset.pnr,
                  ticket: this.dataset.ticket,
                  paid: this.dataset.paid,
                  due: this.dataset.due
              };
              addToCart(passenger);
              alert("Added to Cart!");
          });
      });

      updateCartCount();
  });
</script>

<body>

<?php include 'nav.php'  ?>

<h2 style="margin-top: 10px; text-align:center">Sales Records</h2>
<div style="display:flex; justify-content: center;">
<!-- Search Form -->
<form method="GET" class="search-container" >
    <select name="company">
        <option value="">Select All</option>
        <?php while ($row = $companyResult->fetch_assoc()) : ?>
            <option value="<?= htmlspecialchars($row['PartyName']) ?>" 
                <?= (isset($_GET['company']) && $_GET['company'] == $row['PartyName']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['PartyName']) ?>
            </option>
        <?php endwhile; ?>
    </select>
    
    <input type="text" name="invoice" placeholder="Search Invoice Number" 
        value="<?= isset($_GET['invoice']) ? htmlspecialchars($_GET['invoice']) : '' ?>">

    <input type="text" name="pnr" placeholder="Search PNR" 
        value="<?= isset($_GET['PNR']) ? htmlspecialchars($_GET['PNR']) : '' ?>">
    <button type="submit">Search</button>
</form>
</div>
<!-- Sales Records Table -->
 <div class="result">
<table>
    <tr>
        <th style="font-size: 12px;">Company Name</th>
        <th style="font-size: 12px;">Pessenger Name</th>
        <th style="font-size: 12px;">Invoice Number</th>
        <th style="font-size: 12px;">Route</th>
        <th style="font-size: 12px;">Airlines name</th>
        <th style="font-size: 12px;">PNR</th>
        <th style="font-size: 12px;">Ticket Number</th>
        <th style="font-size: 12px;">Issue Date</th>
        <th style="font-size: 12px;">Day Passes</th>
        <th style="font-size: 12px;">Payment Status</th>
        <th style="font-size: 12px;">Selling Price</th>
        <th style="font-size: 12px;">Sells Person</th>
        <th style="font-size: 12px;">Modify</th>
    </tr>
    <?php while ($row = $salesResult->fetch_assoc()) : 
        $issue_date = new DateTime($row['IssueDate']);
        $today = new DateTime();
        $interval = $issue_date->diff($today);
        $day_passes = $interval->days;
        $deperture_date = new DateTime($row['FlightDate']);
        $return_date = new DateTime($row['ReturnDate']);
        $paidAmount = (float) $row['PaidAmount']; // Ensure it's a float
        // $paid = number_format($paidAmount, 2, '.', ''); // Format as decimal(10,2)
        // $due = number_format($row['BillAmount'], 2) - $paid;
        $paid = isset($row['PaidAmount']) ? (float) $row['PaidAmount'] : 0.00; // Convert safely to float
        $due = number_format($paid, 2, '.', ''); // Format as decimal(10,2)
        ?>
        <tr>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['PartyName']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['PassengerName']) ?></td>
            <td style="font-size: 12px;">
  <?= htmlspecialchars($row['invoice_number']) ?>
  <?php if (!empty($row['invoice_number'])): ?>
    <div>
      <a href="redirect_reissue.php?id=<?= $row['SaleID'] ?>" class="btn btn-success btn-sm mt-1">Reissue</a>
      <a href="edit_invoice.php?id=<?= $row['SaleID'] ?>" class="btn btn-warning btn-sm mt-1">Refund</a>
      <div><small style="color: green;">✔ Invoice generated</small></div>
    </div>
  <?php endif; ?>
</td>

            <td style="font-size: 12px;"><?= htmlspecialchars($row['TicketRoute']) ?>
        </td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['airlines']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['PNR']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['TicketNumber']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['IssueDate']) ?></td>
            <td style="font-size: 12px;"><?= $day_passes ?> days</td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['PaymentStatus']) ?></td>
            <td style="font-size: 12px;">BDT<?= number_format($row['BillAmount'], 2) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($row['SalesPersonName']) ?></td>
            <td>
    <?php if (isset($row['SaleID'])): ?>
        <a href="redirect_edit.php?id=<?php echo htmlspecialchars($row['SaleID']); ?>" class="btn edit-btn" >
            <i class="fas fa-edit"></i> Edit
        </a><br>
        <a href="invoice_list.php?delete=<?php echo htmlspecialchars($row['SaleID']); ?>" class="btn delete-btn" 
           onclick="return confirm('Are you sure you want to delete this record?')">
            <i class="fas fa-trash"></i> Delete
            
        </a>
        <!-- <button class="add-to-cart" 
        data-name="<?php $row['PassengerName'] ?>" 
        data-price="<?php $row['BillAmount'] ?>"
        data-route="<?php $row['TicketRoute'] ?>"
        data-journey="<?php $row['FlightDate'] ?>"
        data-return="<?php $row['ReturnDate'] ?>"
        data-pnr="<?php $row['PNR'] ?>"
        data-ticket="<?php $row['TicketNumber'] ?>"
        data-paid="<?php echo $paid ?>"
        data-due="<?php echo $due ?>">
  Add to Cart
</button> -->
<form action="invoice_cart2.php" method="POST">
    <input type="hidden" name="sell_id" value="<?= $row['SaleID'] ?>">
    <button style="margin-top: 10px;" type="submit" class="btn btn-primary btn-sm">Add to Invoice</button>
</form>

<!-- 
        <a href="cart.php?id=<?php echo htmlspecialchars($row['SaleID']); ?>" class="btn edit-btn" >
        <i class="fas fa-cart-plus"> Invoice</i>        
        </a> -->
        <!-- <a href="cart.php">
        <button onclick="addToCart(<?php echo $product_id; ?>, '<?php echo $product_name; ?>', <?php echo $price; ?>)">
    Add to Invoice
        </button>

        </a> -->

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
