<?php
session_start();

$orderId = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

if (!$orderId) {
    header("Location: cart.php");
    exit;
}

$host = "localhost";
$username = "root";
$password = "";
$database = "da_convenience_store";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$stmt = $conn->prepare(
    "SELECT id, customer_name, phone, address, total, created_at
     FROM orders
     WHERE id = ?"
);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: product.html");
    exit;
}

$stmt = $conn->prepare(
    "SELECT product_name, price, quantity, subtotal
     FROM order_items
     WHERE order_id = ?
     ORDER BY id ASC"
);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$itemCount = 0;

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $itemCount += (int) $row["quantity"];
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Confirmation | D & A Convenience Store</title>
    <link rel="stylesheet" href="../styles/product.css" />
    <link rel="stylesheet" href="../styles/cart.css?v=9" />
</head>

<body class="cart-page-body">

<header>
    <div class="header-main">

        <section class="header-logo">
            <img src="../images/logo1.jpg" alt="D & A Logo">

            <div class="store-name">
                <h2>
                    <span class="orange"><i>D & A</i></span>
                    <span class="blue"><i>CONVENIENCE STORE</i></span>
                </h2>

                <div class="korean-line">
                    <hr class="line">
                    <span class="korean"><i>(편의점)</i></span>
                    <hr class="line">
                </div>
            </div>
        </section>

        <nav class="header-nav">
            <ul>
                <li><a href="../../index.html">HOME</a></li>
                <li><a href="product.html">PRODUCTS</a></li>
                <li><a href="about.html">ABOUT D & A</a></li>
                <li><a href="cart.php">MY CART</a></li>
            </ul>
        </nav>

    </div>
</header>

<main class="cart-page">

    <section class="cart-heading">
        <p class="cart-eyebrow">ORDER CONFIRMATION</p>
        <h1>Thank you, <?= htmlspecialchars($order["customer_name"]) ?>!</h1>
        <p>Your order has been placed successfully.</p>
    </section>

    <section class="cart-container">

        <div class="cart-products">

            <div class="cart-title">
                <h2>Order #<?= (int) $order["id"] ?></h2>
                <span>
                    <?= date(
                        "M d, Y h:i A",
                        strtotime($order["created_at"])
                    ) ?>
                </span>
            </div>

            <div class="order-customer">
                <p>
                    <strong>Name:</strong>
                    <?= htmlspecialchars($order["customer_name"]) ?>
                </p>
                <p>
                    <strong>Phone Number:</strong>
                    <?= htmlspecialchars(
                        $order["phone"] !== "" ? $order["phone"] : "—"
                    ) ?>
                </p>
                <p>
                    <strong>Delivery Address:</strong><br>
                    <?= nl2br(htmlspecialchars($order["address"])) ?>
                </p>
            </div>

            <div class="order-items">
                <?php foreach ($items as $item): ?>
                    <div class="summary-item">
                        <span>
                            <?= htmlspecialchars($item["product_name"]) ?>
                            × <?= (int) $item["quantity"] ?>
                        </span>
                        <span>
                            ₱<?= number_format(
                                (float) $item["subtotal"],
                                2
                            ) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-total">
                <span>Total (<?= $itemCount ?> item<?= $itemCount === 1 ? "" : "s" ?>)</span>
                <strong>₱<?= number_format((float) $order["total"], 2) ?></strong>
            </div>

        </div>

        <aside class="cart-summary">
            <h2>What's Next?</h2>
            <p class="summary-note">
                We have received your order. Please prepare the payment
                upon delivery. Thank you for shopping with D & A!
            </p>
            <a href="product.html" class="continue-button">
                Continue Shopping
            </a>
            <a href="../../index.html" class="back-to-cart">
                Back to Home
            </a>
        </aside>

    </section>
</main>

<footer class="cart-footer">
    <p>&copy; <?= date("Y") ?> D & A Convenience Store.</p>
    <p>Your convenience is our priority.</p>
</footer>

</body>
</html>

<?php
$conn->close();
?>
