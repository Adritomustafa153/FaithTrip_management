<?php
session_start();

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add item to cart via AJAX
if (isset($_POST['add_to_cart'])) {
    $item = [
        'id' => $_POST['id'],
        'name' => $_POST['name'],
        'price' => $_POST['price'],
        'quantity' => 1
    ];

    $_SESSION['cart'][] = $item;
    echo json_encode(['status' => 'success', 'cart_count' => count($_SESSION['cart'])]);
    exit;
}

// Get cart items via AJAX
if (isset($_GET['get_cart'])) {
    echo json_encode($_SESSION['cart']);
    exit;
}

// Clear cart via AJAX
if (isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    echo json_encode(['status' => 'success', 'cart_count' => 0]);
    exit;
}

// Generate bill via AJAX
if (isset($_POST['generate_bill'])) {
    $invoice = "Invoice:\n";
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $invoice .= $item['name'] . " - $" . $item['price'] . "\n";
        $total += $item['price'];
    }
    $invoice .= "Total: $" . $total . "\n";
    
    $_SESSION['cart'] = []; // Clear cart after generating bill
    echo json_encode(['status' => 'success', 'invoice' => nl2br($invoice), 'cart_count' => 0]);
    exit;
}
?>

<!-- Add to Cart Button -->
<a href="#" onclick="addToCart(<?php echo $row['SaleID']; ?>, '<?php echo htmlspecialchars($row['SaleName']); ?>', <?php echo $row['SalePrice']; ?>)" class="btn edit-btn">
    <i class="fas fa-cart-plus"></i> Invoice
</a>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function updateCartCount() {
        $.get('shopping_cart.php?get_cart', function(data) {
            let cart = JSON.parse(data);
            $('#cart-count').text(cart.length);
        });
    }

    function addToCart(id, name, price) {
        $.post('shopping_cart.php', { add_to_cart: true, id: id, name: name, price: price }, function(response) {
            $('#cart-count').text(response.cart_count);
            alert('Item added to cart');
        }, 'json');
    }

    function viewCart() {
        $.get('shopping_cart.php?get_cart', function(data) {
            let cart = JSON.parse(data);
            let cartHtml = '';
            cart.forEach(item => {
                cartHtml += `<li>${item.name} - $${item.price}</li>`;
            });
            $('#cart-items').html(cartHtml);
        });
    }

    function generateBill() {
        $.post('shopping_cart.php', { generate_bill: true }, function(response) {
            alert(response.invoice.replace(/<br\s*\/?>/gi, "\n"));
            $('#cart-count').text(response.cart_count);
            $('#cart-items').html('');
        }, 'json');
    }

    function clearCart() {
        $.post('shopping_cart.php', { clear_cart: true }, function(response) {
            alert(response.message);
            $('#cart-count').text(response.cart_count);
            $('#cart-items').html('');
        }, 'json');
    }

    $(document).ready(function() {
        updateCartCount();
    });
</script>
