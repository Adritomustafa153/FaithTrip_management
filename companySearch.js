$(document).ready(function() {
    // Load Agents
    $("#companysearch").on("keyup", function() {
        let query = $(this).val();
        $.post("fetch_company.php", { search: query }, function(data) {
            $("#companyDropdown").html(data);
        });
    });


    // Load Salespersons
    $.get("fetch_salespersons.php", function(data) {
        $("#salespersonDropdown").html(data);
    });

    // Load Payment Status
    $.get("fetch_payment_status.php", function(data) {
        $("#paymentStatus").html(data);
    });

    // Load Banks
    $.get("fetch_banks.php", function(data) {
        $("#bankDropdown").html(data);
    });

    // Auto-calculate Profit & Due Amount
    $("#billAmount, #netPayment").on("input", function() {
        let bill = parseFloat($("#billAmount").val()) || 0;
        let net = parseFloat($("#netPayment").val()) || 0;
        $("#profit").val(bill - net);
    });

    $("#billAmount, #paidAmount").on("input", function() {
        let bill = parseFloat($("#billAmount").val()) || 0;
        let paid = parseFloat($("#paidAmount").val()) || 0;
        $("#dueAmount").val(bill - paid);
    });

    // Enable/Disable Bank Details
    $("#paymentStatus").on("change", function() {
        let status = $(this).val();
        if (status === "DUE") {
            $("#bankDetails").addClass("hidden");
        } else {
            $("#bankDetails").removeClass("hidden");
        }
    });
});
