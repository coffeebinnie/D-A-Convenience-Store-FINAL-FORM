<?php
require __DIR__ . "/init.php";
requireAdmin();

$totalOrders = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM orders"
)->fetch_assoc()["total"];

$totalSales = (float) $conn->query(
    "SELECT COALESCE(SUM(total), 0) AS total FROM orders"
)->fetch_assoc()["total"];

$totalProducts = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM products"
)->fetch_assoc()["total"];

$lowStock = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM products WHERE stock <= 10"
)->fetch_assoc()["total"];

$outOfStock = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM products WHERE stock = 0"
)->fetch_assoc()["total"];

$bestSellers = $conn->query(
    "SELECT
        order_items.product_name,
        SUM(order_items.quantity) AS sold,
        SUM(order_items.subtotal) AS revenue,
        COALESCE(MAX(products.stock), 0) AS stock
     FROM order_items
     LEFT JOIN products ON products.id = order_items.product_id
     GROUP BY order_items.product_id, order_items.product_name
     ORDER BY sold DESC, revenue DESC
     LIMIT 8"
);

$recentOrders = $conn->query(
    "SELECT id, customer_name, total, status, created_at
     FROM orders
     ORDER BY created_at DESC, id DESC
     LIMIT 5"
);

$lowStockProducts = $conn->query(
    "SELECT name, stock
     FROM products
     WHERE stock <= 10
     ORDER BY stock ASC, name ASC
     LIMIT 8"
);

$pageTitle = "Dashboard";
$activePage = "dashboard";
require __DIR__ . "/_header.php";
?>

<div class="admin-page-heading">
    <div>
        <p class="admin-eyebrow">Store Overview</p>
        <h1>Dashboard</h1>
        <p>Monitor sales, orders, and product stock at a glance.</p>
    </div>
    <a href="products.php" class="admin-button">Add Product</a>
</div>

<section class="stat-grid">
    <article class="stat-card">
        <span>Total Orders</span>
        <strong><?= $totalOrders ?></strong>
        <small>All time orders</small>
    </article>
    <article class="stat-card">
        <span>Total Sales</span>
        <strong><?= money($totalSales) ?></strong>
        <small>All time revenue</small>
    </article>
    <article class="stat-card">
        <span>Products</span>
        <strong><?= $totalProducts ?></strong>
        <small>Items in catalog</small>
    </article>
    <article class="stat-card stat-card-warning">
        <span>Low / Out of Stock</span>
        <strong><?= $lowStock ?></strong>
        <small><?= $outOfStock ?> out of stock</small>
    </article>
</section>

<section class="admin-grid">

    <article class="admin-panel">
        <div class="panel-heading">
            <div>
                <h2>Best Selling Products</h2>
                <p>Products ranked by total units sold.</p>
            </div>
            <a href="orders.php" class="panel-link">View Orders</a>
        </div>

        <?php if ($bestSellers->num_rows === 0): ?>
            <p class="admin-empty">
                No sales yet. Best sellers will appear after a customer
                places an order.
            </p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Sold</th>
                            <th>Sales</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; ?>
                        <?php while ($product = $bestSellers->fetch_assoc()): ?>
                            <?php $status = stockStatus((int) $product["stock"]); ?>
                            <tr>
                                <td><?= $rank++ ?></td>
                                <td><?= e($product["product_name"]) ?></td>
                                <td><?= (int) $product["sold"] ?></td>
                                <td><?= money((float) $product["revenue"]) ?></td>
                                <td>
                                    <span class="badge badge-<?= $status["class"] ?>">
                                        <?= e($status["label"]) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <article class="admin-panel">
        <div class="panel-heading">
            <div>
                <h2>Recent Orders</h2>
                <p>Latest orders placed by customers.</p>
            </div>
            <a href="orders.php" class="panel-link">See All</a>
        </div>

        <?php if ($recentOrders->num_rows === 0): ?>
            <p class="admin-empty">No orders have been placed yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recentOrders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= (int) $order["id"] ?></td>
                                <td><?= e($order["customer_name"]) ?></td>
                                <td><?= money((float) $order["total"]) ?></td>
                                <td>
                                    <span class="badge badge-pending">
                                        <?= e(ucfirst($order["status"])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

</section>

<section class="admin-panel">
    <div class="panel-heading">
        <div>
            <h2>Low Stock Alerts</h2>
            <p>Products that need restocking soon.</p>
        </div>
        <a href="products.php" class="panel-link">Manage Products</a>
    </div>

    <?php if ($lowStockProducts->num_rows === 0): ?>
        <p class="admin-empty">All products are well stocked.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $lowStockProducts->fetch_assoc()): ?>
                        <?php $status = stockStatus((int) $product["stock"]); ?>
                        <tr>
                            <td><?= e($product["name"]) ?></td>
                            <td><?= (int) $product["stock"] ?></td>
                            <td>
                                <span class="badge badge-<?= $status["class"] ?>">
                                    <?= e($status["label"]) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php
require __DIR__ . "/_footer.php";
$conn->close();
?>
