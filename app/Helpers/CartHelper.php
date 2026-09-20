<?php

declare(strict_types=1);

namespace App\Helpers;


class CartHelper
{
    /**
     * Initialize cart session structure if it doesn't exist
     */
    public static function initCart(): void
    {
        if (!isset($_SESSION['cart'])) {
            // Cart is an array of book_id => ['book' => bookData, 'quantity' => int]
            $_SESSION['cart'] = [];
        }
    }

    /**
     * Get total number of items in cart
     * Loops through cart and sums all quantities
     */
    public static function getCartCount(): int
    {
        if (!isset($_SESSION['cart'])) {
            return 0;
        }

        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    /**
     * Add a book to cart or increase quantity if already exists
     */
    public static function addToCart(array $book): void
    {
        self::initCart();

        $bookId = $book['book_id'];

        if (isset($_SESSION['cart'][$bookId])) {
            // Book already in cart — increase quantity
            $_SESSION['cart'][$bookId]['quantity']++;
        } else {
            // New book — add to cart with quantity 1
            $_SESSION['cart'][$bookId] = [
                'book' => $book,
                'quantity' => 1
            ];
        }
    }

    /**
     * Remove a book from cart completely
     */
    public static function removeFromCart(int $bookId): void
    {
        if (isset($_SESSION['cart'][$bookId])) {
            unset($_SESSION['cart'][$bookId]);
        }
    }

    /**
     * Update quantity of a book in cart
     */
    public static function updateQuantity(int $bookId, int $quantity): void
    {
        if ($quantity <= 0) {
            // If quantity is 0 or less just remove it
            self::removeFromCart($bookId);
            return;
        }

        if (isset($_SESSION['cart'][$bookId])) {
            $_SESSION['cart'][$bookId]['quantity'] = $quantity;
        }
    }

    /**
     * Get cart subtotal
     */
    public static function getSubtotal(): float
    {
        if (!isset($_SESSION['cart'])) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['book']['price'] * $item['quantity'];
        }
        return $total;
    }

    /**
     * Clear entire cart
     */
    public static function clearCart(): void
    {
        $_SESSION['cart'] = [];
    }
}
