<?php
session_start();
$cart = $_SESSION['cart'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Shopping Cart</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<script>
document.addEventListener("DOMContentLoaded", function () {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];

    // Update Cart Count
    function updateCartCount() {
        document.getElementById("cart-count").textContent = cart.length;
    }

    // Check for Duplicate Entry
    function isDuplicate(pnr) {
        return cart.some(item => item.pnr === pnr);
    }

    // Add to Cart
    function addToCart(item) {
    if (isDuplicate(item.pnr)) {
        alert("This item is already in your cart!");
        return;
    }
    cart.push(item);
    localStorage.setItem("cart", JSON.stringify(cart));

    console.log("Cart Updated:", localStorage.getItem("cart")); // Debugging

    updateCartCount();
    alert("Added to Cart!");
}

    function displayCart() {
    let cartList = document.getElementById("cart-items");
    cartList.innerHTML = "";

    console.log("Cart Data:", cart); // Debugging

    if (cart.length === 0) {
        cartList.innerHTML = "<li class='list-group-item'>Cart is empty.</li>";
        return;
    }

    cart.forEach((item, index) => {
        let listItem = document.createElement("li");
        listItem.classList.add("list-group-item");
        listItem.innerHTML = `
            <strong>${item.name}</strong><br>
            Route: ${item.route}<br>
            Journey: ${item.journey} | Return: ${item.return}<br>
            Ticket: ${item.ticket} | PNR: ${item.pnr}<br>
            Price: $${item.price} | Paid: $${item.paid} | Due: $${item.due}
            <button class="btn btn-sm btn-danger float-end remove-item" data-index="${index}">Remove</button>
        `;
        cartList.appendChild(listItem);
    });

    // Ensure Remove Buttons Work
    document.querySelectorAll(".remove-item").forEach(button => {
        button.addEventListener("click", function () {
            let index = this.getAttribute("data-index");
            cart.splice(index, 1);
            localStorage.setItem("cart", JSON.stringify(cart));
            displayCart();
            updateCartCount();
        });
    });
}


    // Clear Cart
    document.getElementById("clear-cart").addEventListener("click", function () {
        cart = [];
        localStorage.setItem("cart", JSON.stringify(cart));
        displayCart();
        updateCartCount();
    });

    // Add to Cart Event
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
        });
    });

    // View Cart Event
    document.getElementById("view-cart").addEventListener("click", function () {
        displayCart();
        let cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
        cartModal.show();
    });

    updateCartCount();
});
</script>

<body>
<div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cartModalLabel">Your Cart</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul id="cart-items" class="list-group"></ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" id="clear-cart">Clear Cart</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
</body>
</html>
