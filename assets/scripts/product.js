const categoryList = document.getElementById("category-list");

const escapeHtml = (value) =>
    String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

const formatPrice = (value) =>
    "\u20B1" +
    Number(value).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const productImage = (image) =>
    image ? `../images/${encodeURIComponent(image)}` : "";

function renderProduct(product) {
    const image = productImage(product.image);
    const name = escapeHtml(product.name);
    const productId = Number(product.id);

    return `
        <form method="POST" action="cart.php" class="product-add-form">
            <div class="product-info">
                ${
                    image
                        ? `<img src="${image}" alt="${name}" class="product-thumb">`
                        : `<span class="product-thumb product-thumb-placeholder">🛍️</span>`
                }
                <div>
                    <p class="product-name">${name}</p>
                    <p class="product-price">${formatPrice(product.price)}</p>
                </div>
            </div>
            <div class="product-actions">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="${productId}">
                <label>
                    <span>Qty</span>
                    <input
                        type="number"
                        name="quantity"
                        value="1"
                        min="1"
                        max="99"
                        inputmode="numeric"
                        aria-label="Quantity for ${name}"
                        required
                    >
                </label>
                <button type="submit" class="add-to-cart-button">
                    Add to Cart
                </button>
            </div>
        </form>
    `;
}

function renderCategory(category) {
    const name = escapeHtml(category.name);
    const image = productImage(category.image);
    const count = category.products.length;
    const products = category.products.map(renderProduct).join("");

    return `
        <article class="offer-card category-card" data-category-id="${Number(
            category.id
        )}">
            <button
                type="button"
                class="category-toggle"
                aria-expanded="false"
            >
                ${
                    image
                        ? `<img src="${image}" alt="${name}">`
                        : `<span class="category-image-placeholder">🛍️</span>`
                }
                <span class="category-name">${name}</span>
                <span class="category-count">${count} item${
        count === 1 ? "" : "s"
    }</span>
                <span class="category-chevron" aria-hidden="true">⌄</span>
            </button>
            <div class="category-products" hidden>
                ${
                    count
                        ? products
                        : `<p class="no-products">No products available yet.</p>`
                }
            </div>
        </article>
    `;
}

function closeCategory(card) {
    card.classList.remove("open");
    card.querySelector(".category-toggle").setAttribute(
        "aria-expanded",
        "false"
    );
    card.querySelector(".category-products").hidden = true;
}

function openCategory(card) {
    card.classList.add("open");
    card.querySelector(".category-toggle").setAttribute("aria-expanded", "true");
    card.querySelector(".category-products").hidden = false;
}

categoryList.addEventListener("click", (event) => {
    const toggle = event.target.closest(".category-toggle");

    if (!toggle) {
        return;
    }

    const card = toggle.closest(".category-card");
    const isOpen = card.classList.contains("open");

    categoryList
        .querySelectorAll(".category-card.open")
        .forEach((openCard) => closeCategory(openCard));

    if (!isOpen) {
        openCategory(card);
    }
});

categoryList.addEventListener("submit", (event) => {
    const form = event.target.closest(".product-add-form");

    if (!form) {
        return;
    }

    const button = form.querySelector(".add-to-cart-button");

    if (button) {
        button.textContent = "Adding...";
        button.disabled = true;
    }
});

fetch("products_api.php")
    .then((response) => {
        if (!response.ok) {
            throw new Error("Unable to load products");
        }

        return response.json();
    })
    .then((categories) => {
        if (!Array.isArray(categories) || categories.length === 0) {
            categoryList.innerHTML =
                '<p class="products-message">No products available yet.</p>';
            return;
        }

        categoryList.innerHTML = categories.map(renderCategory).join("");
    })
    .catch(() => {
        categoryList.innerHTML =
            '<p class="products-message">Unable to load products. Please refresh the page.</p>';
    });
