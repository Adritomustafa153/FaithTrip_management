document.addEventListener("DOMContentLoaded", function () {
    const darkModeToggle = document.getElementById("darkModeToggle");

    // Check for saved preference
    if (localStorage.getItem("dark-mode") === "enabled") {
        document.body.classList.add("dark");
        darkModeToggle.checked = true;
    }

    darkModeToggle.addEventListener("change", function () {
        if (this.checked) {
            document.body.classList.add("dark");
            localStorage.setItem("dark-mode", "enabled");
        } else {
            document.body.classList.remove("dark");
            localStorage.setItem("dark-mode", "disabled");
        }
    });
});
