function updateDateTime() {
    const now = new Date();
    const dateTimeElement = document.getElementById('date-time');
    dateTimeElement.innerText = now.toLocaleString();
}
function updateDashboard() {
  const filter = document.getElementById('salesFilter').value;
  window.location.href = `dashboard.php?filter=${filter}`;
}


setInterval(updateDateTime, 1000);
updateDateTime();

document.querySelectorAll('.dropdown-submenu .dropdown-toggle').forEach(function(el) {
  el.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    el.nextElementSibling.classList.toggle('show');
  });
});
