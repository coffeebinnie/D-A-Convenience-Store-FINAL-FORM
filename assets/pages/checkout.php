<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "da_convenience_store";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$sql = "SELECT
            cart.id AS cart_id,
            products.id AS product_id,
            products.name,
            products.price,
            products.image,
            cart.quantity,
            (products.price * cart.quantity) AS subtotal
        FROM cart
        INNER JOIN products
        ON cart.product_id = products.id
        ORDER BY cart.id DESC";

$result = $conn->query($sql);

$items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total += (float) $row["subtotal"];
}

$itemCount = count($items);

if ($itemCount === 0) {
    header("Location: cart.php");
    exit;
}

$error = "";
$customerName = "";
$phone = "";
$address = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $customerName = trim($_POST["customer_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $phoneDigits = preg_replace('/\D/', '', $phone);

    if ($customerName === "" || $phone === "" || $address === "") {
        $error = "Please enter your name, phone number, and delivery address.";
    } elseif (
        !preg_match('/^[0-9+() -]{7,30}$/', $phone)
        || strlen($phoneDigits) < 7
        || strlen($phoneDigits) > 15
    ) {
        $error = "Please enter a valid phone number.";
    } else {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare(
                "INSERT INTO orders (customer_name, phone, address, total)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sssd",
                $customerName,
                $phone,
                $address,
                $total
            );
            $stmt->execute();
            $orderId = (int) $conn->insert_id;
            $stmt->close();

            $itemStmt = $conn->prepare(
                "INSERT INTO order_items
                    (order_id, product_id, product_name, price, quantity, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            foreach ($items as $item) {
                $productId = (int) $item["product_id"];
                $productName = $item["name"];
                $price = (float) $item["price"];
                $quantity = (int) $item["quantity"];
                $subtotal = (float) $item["subtotal"];

                $itemStmt->bind_param(
                    "iisdid",
                    $orderId,
                    $productId,
                    $productName,
                    $price,
                    $quantity,
                    $subtotal
                );
                $itemStmt->execute();
            }

            $itemStmt->close();

            $conn->query("DELETE FROM cart");
            $conn->commit();

            header("Location: order_success.php?order_id=" . $orderId);
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $error = "Unable to place your order. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Checkout | D & A Convenience Store</title>
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
                <li><a href="cart.php">MY CART (<?= $itemCount ?>)</a></li>
            </ul>
        </nav>

    </div>
</header>

<main class="cart-page">

    <section class="cart-heading">
        <p class="cart-eyebrow">D & A CONVENIENCE STORE</p>
        <h1>Checkout</h1>
        <p>Enter your delivery details to place your order.</p>
    </section>

    <section class="cart-container">

        <div class="cart-products">

            <div class="cart-title">
                <h2>Customer Information</h2>
                <span><?= $itemCount ?> item(s)</span>
            </div>

            <?php if ($error !== ""): ?>
                <p class="form-error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST" action="checkout.php" class="checkout-form">
                <label for="customer_name">
                    Full Name
                    <input
                        type="text"
                        id="customer_name"
                        name="customer_name"
                        value="<?= htmlspecialchars(
                            $customerName,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        maxlength="150"
                        autocomplete="name"
                        required
                    >
                </label>

                <label for="phone">
                    Phone Number
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars(
                            $phone,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        maxlength="30"
                        pattern="[0-9+() -]{7,30}"
                        autocomplete="tel"
                        required
                    >
                </label>

                <label for="address">
                    Delivery Address
                    <textarea
                        id="address"
                        name="address"
                        rows="4"
                        autocomplete="street-address"
                        required
                    ><?= htmlspecialchars(
                        $address,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?></textarea>
                </label>

                <div class="checkout-actions">
                    <button type="submit" class="place-order-button">
                        Place Order
                    </button>
                    <a href="cart.php" class="back-to-cart">Back to Cart</a>
                </div>
            </form>

        </div>

        <aside class="cart-summary">
            <h2>Order Summary</h2>

            <?php foreach ($items as $item): ?>
                <div class="summary-item">
                    <span>
                        <?= htmlspecialchars($item["name"]) ?>
                        × <?= (int) $item["quantity"] ?>
                    </span>
                    <span>
                        ₱<?= number_format((float) $item["subtotal"], 2) ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <div class="summary-row">
                <span>Products</span>
                <span><?= $itemCount ?></span>
            </div>
            <div class="summary-row">
                <span>Subtotal</span>
                <span>₱<?= number_format($total, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Delivery</span>
                <span>₱0.00</span>
            </div>
            <div class="summary-total">
                <span>Total</span>
                <strong>₱<?= number_format($total, 2) ?></strong>
            </div>
            <p class="summary-note">
                Prices are displayed in Philippine pesos (PHP).
            </p>
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
