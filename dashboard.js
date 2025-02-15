function updateDateTime() {
    const now = new Date();
    const dateTimeElement = document.getElementById('date-time');
    dateTimeElement.innerText = now.toLocaleString();
}

setInterval(updateDateTime, 1000);
updateDateTime();
