<?php
require __DIR__ . "/init.php";
requireAdmin();

$error = "";
$successMessage = "";
$search = trim($_GET["q"] ?? "");
$returnQuery = $search;

if (isset($_GET["added"])) {
    $successMessage = "Product added successfully.";
} elseif (isset($_GET["stock_added"])) {
    $successMessage = "Stock added successfully.";
} elseif (isset($_GET["deleted"])) {
    $successMessage = "Product removed successfully.";
}

$formName = "";
$formCategory = 0;
$formPrice = "";
$formStock = "0";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "add_product";
    $token = $_POST["csrf_token"] ?? null;
    $returnQuery = trim($_POST["return_q"] ?? "");

    if ($returnQuery !== "") {
        $search = $returnQuery;
    }

    if (!verifyCsrf($token)) {
        $error = "Your session expired. Please try again.";
    } elseif ($action === "add_stock") {
        $productId = (int) filter_input(
            INPUT_POST,
            "product_id",
            FILTER_VALIDATE_INT
        );
        $addStock = filter_input(
            INPUT_POST,
            "add_stock",
            FILTER_VALIDATE_INT
        );

        if ($productId <= 0) {
            $error = "Please choose a valid product.";
        } elseif ($addStock === false || $addStock < 1) {
            $error = "Please enter a valid stock amount.";
        } elseif ($addStock > 100000) {
            $error = "Stock amount is too large.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE products SET stock = stock + ? WHERE id = ?"
            );
            $stmt->bind_param("ii", $addStock, $productId);
            $stmt->execute();
            $updated = $stmt->affected_rows;
            $stmt->close();

            if ($updated === 1) {
                $redirect = "products.php?stock_added=1";

                if ($returnQuery !== "") {
                    $redirect .= "&q=" . urlencode($returnQuery);
                }

                header("Location: " . $redirect);
                exit;
            }

            $error = "Product not found.";
        }
    } elseif ($action === "delete_product") {
        $productId = (int) filter_input(
            INPUT_POST,
            "product_id",
            FILTER_VALIDATE_INT
        );

        if ($productId <= 0) {
            $error = "Please choose a valid product.";
        } else {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            if ($deleted === 1) {
                $redirect = "products.php?deleted=1";

                if ($returnQuery !== "") {
                    $redirect .= "&q=" . urlencode($returnQuery);
                }

                header("Location: " . $redirect);
                exit;
            }

            $error = "Product not found.";
        }
    } elseif ($action === "add_product") {
        $formName = trim($_POST["name"] ?? "");
        $formCategory = (int) filter_input(
            INPUT_POST,
            "category_id",
            FILTER_VALIDATE_INT
        );
        $formPrice = trim($_POST["price"] ?? "");
        $formStock = trim($_POST["stock"] ?? "");

        $price = filter_var($formPrice, FILTER_VALIDATE_FLOAT);
        $stock = filter_var($formStock, FILTER_VALIDATE_INT);

        if ($formName === "") {
            $error = "Please enter the product name.";
        } elseif (mb_strlen($formName) > 150) {
            $error = "Product name must be 150 characters or fewer.";
        } elseif ($formCategory <= 0) {
            $error = "Please choose a category.";
        } elseif ($price === false || $price < 0) {
            $error = "Please enter a valid price.";
        } elseif ($stock === false || $stock < 0) {
            $error = "Please enter a valid stock quantity.";
        } else {
            $stmt = $conn->prepare(
                "SELECT id FROM categories WHERE id = ? LIMIT 1"
            );
            $stmt->bind_param("i", $formCategory);
            $stmt->execute();
            $category = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$category) {
                $error = "The selected category does not exist.";
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO products (category_id, name, price, stock)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "isdi",
                    $formCategory,
                    $formName,
                    $price,
                    $stock
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: products.php?added=1");
                    exit;
                }

                $stmt->close();
                $error = "Unable to add the product. Please try again.";
            }
        }
    } else {
        $error = "Invalid action.";
    }
}

$categoryOptions = $conn->query(
    "SELECT id, name FROM categories ORDER BY sort_order ASC"
)->fetch_all(MYSQLI_ASSOC);

$productSql = "SELECT
        products.id,
        products.name,
        products.price,
        products.stock,
        categories.id AS category_id,
        categories.name AS category_name
     FROM products
     LEFT JOIN categories ON categories.id = products.category_id";

if ($search !== "") {
    $productSql .= " WHERE products.name LIKE ? OR categories.name LIKE ?";
}

$productSql .= " ORDER BY
        categories.sort_order ASC,
        categories.name ASC,
        products.name ASC";

$stmt = $conn->prepare($productSql);

if ($search !== "") {
    $like = "%" . $search . "%";
    $stmt->bind_param("ss", $like, $like);
}

$stmt->execute();
$products = $stmt->get_result();
$productCount = $products->num_rows;

$categoryGroups = [];

while ($product = $products->fetch_assoc()) {
    $categoryId =
        $product["category_id"] === null
            ? 0
            : (int) $product["category_id"];

    if (!isset($categoryGroups[$categoryId])) {
        $categoryGroups[$categoryId] = [
            "name" => $product["category_name"] ?? "Uncategorized",
            "products" => [],
            "count" => 0,
            "stock" => 0,
        ];
    }

    $categoryGroups[$categoryId]["products"][] = $product;
    $categoryGroups[$categoryId]["count"]++;
    $categoryGroups[$categoryId]["stock"] += (int) $product["stock"];
}

$stmt->close();

$pageTitle = "Products";
$activePage = "products";
require __DIR__ . "/_header.php";
?>

<div class="admin-page-heading">
    <div>
        <p class="admin-eyebrow">Catalog Management</p>
        <h1>Products</h1>
        <p>Add products, restock items, and remove products.</p>
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

<section class="admin-grid admin-grid-products">

    <article class="admin-panel">
        <div class="panel-heading">
            <div>
                <h2>Add Product</h2>
                <p>Create a new item for the store catalog.</p>
            </div>
        </div>

        <form method="POST" action="products.php" class="admin-form">
            <input type="hidden" name="action" value="add_product">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <label for="name">
                Product Name
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= e($formName) ?>"
                    maxlength="150"
                    required
                >
            </label>

            <label for="category_id">
                Category
                <select id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categoryOptions as $category): ?>
                        <option
                            value="<?= (int) $category["id"] ?>"
                            <?= $formCategory === (int) $category["id"]
                                ? "selected"
                                : "" ?>
                        >
                            <?= e($category["name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label for="price">
                Price (₱)
                <input
                    type="number"
                    id="price"
                    name="price"
                    value="<?= e($formPrice) ?>"
                    min="0"
                    step="0.01"
                    required
                >
            </label>

            <label for="stock">
                Stock Quantity
                <input
                    type="number"
                    id="stock"
                    name="stock"
                    value="<?= e($formStock) ?>"
                    min="0"
                    step="1"
                    required
                >
            </label>

            <p class="form-hint">
                A placeholder image will be used on the storefront.
            </p>

            <button type="submit" class="admin-button">Add Product</button>
        </form>
    </article>

    <article class="admin-panel">
        <div class="panel-heading">
            <div>
                <h2>Product List</h2>
                <?php if ($search !== ""): ?>
                    <p>
                        <?= $productCount ?> result(s) for
                        "<?= e($search) ?>"
                    </p>
                <?php else: ?>
                    <p>
                        <?= $productCount ?> product(s) in
                        <?= count($categoryGroups) ?> categories.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <form method="GET" action="products.php" class="product-search">
            <input
                type="search"
                name="q"
                value="<?= e($search) ?>"
                placeholder="Search products or categories..."
                aria-label="Search products or categories"
            >
            <button type="submit" class="admin-button button-small">
                Search
            </button>
            <?php if ($search !== ""): ?>
                <a href="products.php" class="search-clear">Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($productCount === 0): ?>
            <p class="admin-empty">
                <?php if ($search !== ""): ?>
                    No products match "<?= e($search) ?>".
                <?php else: ?>
                    No products yet. Add your first product.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <div class="category-list">
                <?php foreach ($categoryGroups as $group): ?>
                    <details
                        class="category-group"
                        <?= $search !== "" ? "open" : "" ?>
                    >
                        <summary>
                            <span class="category-name">
                                <?= e($group["name"]) ?>
                            </span>
                            <span class="category-meta">
                                <?= $group["count"] ?>
                                <?= $group["count"] === 1
                                    ? "product"
                                    : "products" ?>
                                &middot;
                                <?= $group["stock"] ?> in stock
                            </span>
                        </summary>

                        <div class="table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group["products"] as $product): ?>
                                        <?php $status = stockStatus((int) $product["stock"]); ?>
                                        <tr>
                                            <td><?= e($product["name"]) ?></td>
                                            <td><?= money((float) $product["price"]) ?></td>
                                            <td><?= (int) $product["stock"] ?></td>
                                            <td>
                                                <span class="badge badge-<?= $status["class"] ?>">
                                                    <?= e($status["label"]) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="product-actions">
                                                    <form
                                                        method="POST"
                                                        action="products.php"
                                                        class="inline-form"
                                                    >
                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="add_stock"
                                                        >
                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= e(csrfToken()) ?>"
                                                        >
                                                        <input
                                                            type="hidden"
                                                            name="product_id"
                                                            value="<?= (int) $product["id"] ?>"
                                                        >
                                                        <input
                                                            type="hidden"
                                                            name="return_q"
                                                            value="<?= e($search) ?>"
                                                        >
                                                        <input
                                                            type="number"
                                                            name="add_stock"
                                                            value="1"
                                                            min="1"
                                                            step="1"
                                                            aria-label="Stock to add for <?= e($product["name"]) ?>"
                                                            required
                                                        >
                                                        <button
                                                            type="submit"
                                                            class="admin-button button-small"
                                                        >
                                                            Add Stock
                                                        </button>
                                                    </form>

                                                    <form
                                                        method="POST"
                                                        action="products.php"
                                                        class="inline-form"
                                                        onsubmit="return confirm('Remove this product? This cannot be undone.');"
                                                    >
                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="delete_product"
                                                        >
                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= e(csrfToken()) ?>"
                                                        >
                                                        <input
                                                            type="hidden"
                                                            name="product_id"
                                                            value="<?= (int) $product["id"] ?>"
                                                        >
                                                        <input
                                                            type="hidden"
                                                            name="return_q"
                                                            value="<?= e($search) ?>"
                                                        >
                                                        <button
                                                            type="submit"
                                                            class="admin-button button-small button-danger"
                                                        >
                                                            Remove Product
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

</section>

<?php
require __DIR__ . "/_footer.php";
$conn->close();
?>
