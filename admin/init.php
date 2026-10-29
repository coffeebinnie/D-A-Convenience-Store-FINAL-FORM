<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION["admin_id"]);
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function csrfToken(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function verifyCsrf(?string $token): bool
{
    return isset($_SESSION["csrf_token"])
        && is_string($token)
        && hash_equals($_SESSION["csrf_token"], $token);
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function stockStatus(int $stock): array
{
    if ($stock <= 0) {
        return ["label" => "Out of Stock", "class" => "out"];
    }

    if ($stock <= 10) {
        return ["label" => "Low Stock", "class" => "low"];
    }

    return ["label" => "In Stock", "class" => "in"];
}

function money(float $amount): string
{
    return "\u{20B1}" . number_format($amount, 2);
}
