<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

</head>
<script>
    // Update Cart Count in Navbar
function updateCartCount() {
    $.get('shopping_cart.php?get_cart', function(data) {
        let cart = JSON.parse(data);
        $('#cart-count').text(cart.length);
        updateCartDropdown(cart);
    });
}

// Update Cart Dropdown View
function updateCartDropdown(cart) {
    let dropdown = $('#cart-dropdown');
    dropdown.empty();

    if (cart.length === 0) {
        dropdown.append('<li><a class="dropdown-item">Cart is empty</a></li>');
        return;
    }

    cart.forEach(item => {
        dropdown.append(`<li><a class="dropdown-item">${item.name} - $${item.price}</a></li>`);
    });

    dropdown.append('<li><hr class="dropdown-divider"></li>');
    dropdown.append('<li><a class="dropdown-item text-center btn btn-primary" href="cart.php">View Cart</a></li>');
}

// Add to Cart (Attach to Your "Add to Cart" Button)
function addToCart(id, name, price) {
    $.post('shopping_cart.php', { add_to_cart: true, id: id, name: name, price: price }, function(response) {
        $('#cart-count').text(response.cart_count);
        updateCartCount();
        alert('Item added to cart');
    }, 'json');
}

// Initialize on Page Load
$(document).ready(function() {
    updateCartCount();
});

</script>
<body>
<li class="nav-item dropdown">
    <a class="nav-link" href="#" id="cart-icon" data-bs-toggle="dropdown">
        <i class="fas fa-shopping-cart"></i> <span id="cart-count">0</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end" id="cart-dropdown">
        <li><a class="dropdown-item">Cart is empty</a></li>
    </ul>
</li>
</body>
</html>