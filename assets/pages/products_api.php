<?php

header("Content-Type: application/json; charset=utf-8");

$host = "localhost";
$username = "root";
$password = "";
$database = "da_convenience_store";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$conn->set_charset("utf8mb4");

$sql = "SELECT
            categories.id AS category_id,
            categories.name AS category_name,
            categories.image AS category_image,
            products.id AS product_id,
            products.name AS product_name,
            products.price,
            products.image AS product_image
        FROM categories
        LEFT JOIN products
        ON products.category_id = categories.id
        ORDER BY categories.sort_order ASC, products.name ASC";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => "Unable to load products"]);
    exit;
}

$categories = [];

while ($row = $result->fetch_assoc()) {
    $categoryId = (int) $row["category_id"];

    if (!isset($categories[$categoryId])) {
        $categories[$categoryId] = [
            "id" => $categoryId,
            "name" => $row["category_name"],
            "image" => $row["category_image"],
            "products" => [],
        ];
    }

    if ($row["product_id"] !== null) {
        $categories[$categoryId]["products"][] = [
            "id" => (int) $row["product_id"],
            "name" => $row["product_name"],
            "price" => (float) $row["price"],
            "image" => $row["product_image"],
        ];
    }
}

echo json_encode(
    array_values($categories),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

$conn->close();
