document.addEventListener("DOMContentLoaded", function () {
    const companyDropdown = document.querySelector("select[name='company']");
    const searchInput = document.querySelector("input[name='invoice']");
    const searchPNR = document.querySelector("input[name='booking_id']");
    const deleteButtons = document.querySelectorAll(".delete-btn");

    // Auto-submit when company dropdown changes
    companyDropdown.addEventListener("change", function () {
        document.querySelector("form").submit();
    });

    // Real-time invoice search filtering
    searchInput.addEventListener("keyup", function () {
        document.querySelector("form").submit();
    });

        // Real-time invoice search filtering
        searchPNR.addEventListener("keyup", function () {
            document.querySelector("form").submit();
        });

    // Confirmation before deleting a record
    deleteButtons.forEach(button => {
        button.addEventListener("click", function (event) {
            if (!confirm("Are you sure you want to delete this record?")) {
                event.preventDefault();
            }
        });
    });
    
});

