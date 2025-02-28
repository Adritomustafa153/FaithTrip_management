<?php
$conn = new mysqli("localhost", "root", "", "faithtrip_accounts");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare("INSERT INTO hotel 
        (partyName, pessengerName, hotelName, country, address, issue_date, checkin_date, checkout_date, reference_number , selling_price, net_price, profit, payment_status, paid_amount, due_amount, payment_method, bank_name, 
        deposit_date, clearing_date, issued_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $profit = $_POST['billAmount'] - $_POST['NetPayment'];
    $dueAmount = $_POST['billAmount'] - $_POST['PaidAmount'];

    $stmt->bind_param("sssssssssdddsddsssss", 
        $_POST['AgentID'], $_POST['PassengerName'], $_POST['hotelName'],$_POST['country'], $_POST['address'], $_POST['IssueDate'], $_POST['checkindate'], $_POST['checkoutdate'], $_POST['bookingId'], $_POST['billAmount'], $_POST['NetPayment'], $profit, $_POST['PaymentStatus'], $_POST['PaidAmount'], $dueAmount, $_POST['PaymentMethod'], $_POST['BankName'], $_POST['DepositDate'], $_POST['ClearingDate'], $_POST['SalesPersonName']);

    if ($stmt->execute()) {
        echo "Sale recorded successfully!";
        header("Location: hotel_sales.php");
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    $("#bookingId").on("input", function () {
        let bookingId = $(this).val().trim();

        if (bookingId.length > 0) {
            $.ajax({
                url: "check_booking_id.php",
                type: "POST",
                data: { bookingId: bookingId },
                dataType: "json",
                success: function (response) {
                    if (response.exists) {
                        $("#bookingIdWarning").show();
                    } else {
                        $("#bookingIdWarning").hide();
                    }
                },
                error: function () {
                    console.error("Error checking Booking ID.");
                }
            });
        } else {
            $("#bookingIdWarning").hide();
        }
    });

    $("#salesForm").on("submit", function (event) {
        if ($("#bookingIdWarning").is(":visible")) {
            event.preventDefault(); // Prevent form submission
            alert("Booking ID already exists. Please enter a unique ID.");
        }
    });
});
</script>

