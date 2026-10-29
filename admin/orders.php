<?php
require __DIR__ . "/init.php";
requireAdmin();

$error = "";
$successMessage = isset($_GET["updated"])
    ? "Order status updated successfully."
    : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $token = $_POST["csrf_token"] ?? null;

    if (!verifyCsrf($token)) {
        $error = "Your session expired. Please try again.";
    } elseif ($action !== "update_status") {
        $error = "Invalid action.";
    } else {
        $orderId = (int) filter_input(
            INPUT_POST,
            "order_id",
            FILTER_VALIDATE_INT
        );
        $status = $_POST["status"] ?? "";
        $allowedStatuses = ["pending", "delivered"];

        if ($orderId <= 0) {
            $error = "Please choose a valid order.";
        } elseif (!in_array($status, $allowedStatuses, true)) {
            $error = "Please choose a valid order status.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE orders SET status = ? WHERE id = ?"
            );
            $stmt->bind_param("si", $status, $orderId);
            $stmt->execute();
            $stmt->close();

            header("Location: orders.php?updated=1#order-" . $orderId);
            exit;
        }
    }
}

$sql = "SELECT
            orders.id AS order_id,
            orders.customer_name,
            orders.phone,
            orders.address,
            orders.total,
            orders.status,
            orders.created_at,
            order_items.product_name,
            order_items.price,
            order_items.quantity,
            order_items.subtotal,
            products.stock
        FROM orders
        LEFT JOIN order_items ON order_items.order_id = orders.id
        LEFT JOIN products ON products.id = order_items.product_id
        ORDER BY orders.created_at DESC, orders.id DESC";

$result = $conn->query($sql);

$orders = [];

while ($row = $result->fetch_assoc()) {
    $orderId = (int) $row["order_id"];

    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            "id" => $orderId,
            "customer_name" => $row["customer_name"],
            "phone" => $row["phone"],
            "address" => $row["address"],
            "total" => (float) $row["total"],
            "status" => $row["status"],
            "created_at" => $row["created_at"],
            "items" => [],
        ];
    }

    if ($row["product_name"] !== null) {
        $orders[$orderId]["items"][] = [
            "name" => $row["product_name"],
            "price" => (float) $row["price"],
            "quantity" => (int) $row["quantity"],
            "subtotal" => (float) $row["subtotal"],
            "stock" => $row["stock"] === null ? null : (int) $row["stock"],
        ];
    }
}

$pageTitle = "Orders";
$activePage = "orders";
require __DIR__ . "/_header.php";
?>

<div class="admin-page-heading">
    <div>
        <p class="admin-eyebrow">Order Management</p>
        <h1>Ordered List</h1>
        <p>Update order status and review item stock availability.</p>
    </div>
</div>

<?php if ($successMessage !== ""): ?>
    <p class="admin-alert admin-alert-success">
        <?= e($successMessage) ?>
    </p>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <p class="admin-alert admin-alert-error"><?= e($error) ?></p>
<?php endif; ?>

<?php if (count($orders) === 0): ?>
    <section class="admin-panel">
        <p class="admin-empty">
            No orders have been placed yet. Orders will appear here once a
            customer checks out.
        </p>
    </section>
<?php else: ?>

    <div class="order-list">
        <?php foreach ($orders as $order): ?>
            <article class="order-card" id="order-<?= $order["id"] ?>">
                <div class="order-card-header">
                    <div>
                        <h2>Order #<?= $order["id"] ?></h2>
                        <p>
                            <?= date(
                                "M d, Y h:i A",
                                strtotime($order["created_at"])
                            ) ?>
                        </p>
                    </div>
                    <div class="order-card-total">
                        <span>Order Total</span>
                        <strong><?= money($order["total"]) ?></strong>
                    </div>
                </div>

                <div class="order-customer-grid">
                    <div>
                        <h3>Customer</h3>
                        <p><?= e($order["customer_name"]) ?></p>
                    </div>
                    <div>
                        <h3>Phone</h3>
                        <p><?= e($order["phone"] !== "" ? $order["phone"] : "—") ?></p>
                    </div>
                    <div>
                        <h3>Delivery Address</h3>
                        <p><?= nl2br(e($order["address"])) ?></p>
                    </div>
                    <div>
                        <h3>Status</h3>
                        <form
                            method="POST"
                            action="orders.php"
                            class="status-form"
                        >
                            <input
                                type="hidden"
                                name="action"
                                value="update_status"
                            >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>"
                            >
                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= $order["id"] ?>"
                            >
                            <select
                                name="status"
                                aria-label="Status for order <?= $order["id"] ?>"
                            >
                                <option
                                    value="pending"
                                    <?= $order["status"] === "pending"
                                        ? "selected"
                                        : "" ?>
                                >
                                    Pending
                                </option>
                                <option
                                    value="delivered"
                                    <?= $order["status"] === "delivered"
                                        ? "selected"
                                        : "" ?>
                                >
                                    Delivered
                                </option>
                            </select>
                            <button
                                type="submit"
                                class="admin-button button-small"
                            >
                                Update
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (count($order["items"]) === 0): ?>
                    <p class="admin-empty">No items recorded for this order.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order["items"] as $item): ?>
                                    <?php
                                    if ($item["stock"] === null) {
                                        $status = [
                                            "label" => "Unavailable",
                                            "class" => "out",
                                        ];
                                        $stockText = "N/A";
                                    } else {
                                        $status = stockStatus($item["stock"]);
                                        $stockText = $item["stock"] . " left";
                                    }
                                    ?>
                                    <tr>
                                        <td><?= e($item["name"]) ?></td>
                                        <td><?= money($item["price"]) ?></td>
                                        <td><?= $item["quantity"] ?></td>
                                        <td><?= money($item["subtotal"]) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $status["class"] ?>">
                                                <?= e($status["label"]) ?>
                                            </span>
                                            <small class="stock-note">
                                                <?= e($stockText) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php
require __DIR__ . "/_footer.php";
$conn->close();
?>
