<?php

use App\Helpers\ViewHelper;
use App\Helpers\CheckoutSecurity;
use App\Helpers\Core\FlashMessage;

ViewHelper::loadHeader('My Account');
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$customer = $customer ?? [];
$purchases = $purchases ?? [];
$purchaseItems = $purchaseItems ?? [];
$currentPage = $currentPage ?? 1;
$pageCount = $pageCount ?? 1;
$receiptCount = $receiptCount ?? count($purchases);
$receiptsPerPage = $receiptsPerPage ?? 10;
?>
<div class="container py-5">
    <?= FlashMessage::render() ?>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <div class="text-uppercase small text-primary fw-bold">Customer portal</div>
            <h1 class="fw-bold mb-1">Hello, <?= $h($customer['customer_name']) ?></h1>
            <p class="text-muted mb-0">Your membership and receipt history.</p>
        </div>
        <form action="<?= BASE_URL ?>logout" method="post">
            <input type="hidden" name="csrf" value="<?= $h(CheckoutSecurity::csrfToken()) ?>">
            <button class="btn btn-outline-secondary">Log out</button>
        </form>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <div class="membership-card h-100">
                <div class="card-label">IOT Smart Store Membership</div>
                <div class="my-4">
                    <div class="card-label mb-1">Member number</div>
                    <div class="member-number"><?= $h($customer['membership_code']) ?></div>
                </div>
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div>
                        <div class="card-label">Name</div><strong><?= $h($customer['customer_name']) ?></strong>
                    </div>
                    <div>
                        <div class="card-label">Account</div><strong>Active</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card store-card h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <div class="text-muted fw-semibold mb-2">Total loyalty points</div>
                    <div class="metric-number"><?= (int)$customer['total_points'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <section class="card store-card mb-4">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="h4 fw-bold mb-1">Purchase report</h2>
                <p class="text-muted mb-0">Review your spending and the products you purchased by date.</p>
            </div>
            <a class="btn btn-primary" href="<?= BASE_URL ?>account/report">View purchase report</a>
        </div>
    </section>

    <section class="card store-card">
        <div class="card-body p-4">
            <h2 class="h4 fw-bold mb-3">Receipt history</h2>
            <?php if (!$purchases): ?>
                <p class="text-muted mb-0">No receipts found.</p>
            <?php else: ?>
                <?php foreach ($purchases as $order): ?>
                    <article class="receipt-entry py-4 border-top">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                            <div>
                                <h3 class="h6 fw-bold mb-1">Receipt #<?= (int)$order['id'] ?></h3>
                                <time class="text-muted small" datetime="<?= $h((new DateTimeImmutable($order['created_at']))->format(DateTimeInterface::ATOM)) ?>">
                                    <?= $h((new DateTimeImmutable($order['created_at']))->format('Y-m-d \@ H:i')) ?>
                                </time>
                            </div>
                            <div class="text-end">
                                <strong class="money">$<?= number_format((float)$order['total'], 2) ?></strong>
                                <div class="small text-success">+<?= (int)$order['points_earned'] ?> points</div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-3">
                                <thead>
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col" class="text-end">Qty</th>
                                        <th scope="col" class="text-end">Price each</th>
                                        <th scope="col" class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($purchaseItems[(int)$order['id']] ?? [] as $item): ?>
                                        <tr>
                                            <td><?= $h($item['product_name']) ?></td>
                                            <td class="text-end"><?= (int)$item['quantity'] ?></td>
                                            <td class="text-end money">$<?= number_format((float)$item['unit_price'], 2) ?></td>
                                            <td class="text-end money">$<?= number_format((float)$item['unit_price'] * (int)$item['quantity'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <form action="<?= BASE_URL ?>account/resend-receipt" method="post" class="row g-2 align-items-end">
                            <input type="hidden" name="csrf" value="<?= $h(CheckoutSecurity::csrfToken()) ?>">
                            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                            <div class="col-md-7">
                                <label class="form-label small" for="receipt_email_<?= (int)$order['id'] ?>">Email receipt to</label>
                                <input type="email" class="form-control" id="receipt_email_<?= (int)$order['id'] ?>" name="receipt_email"
                                    value="<?= $h($customer['email_address']) ?>" required>
                            </div>
                            <div class="col-md-5"><button type="submit" class="btn btn-outline-primary w-100">Resend receipt</button></div>
                        </form>
                    </article>
                <?php endforeach; ?>
                <?php if ($pageCount > 1): ?>
                    <nav class="mt-4" aria-label="Receipt pages">
                        <ul class="pagination justify-content-center flex-wrap mb-0">
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <?php if ($currentPage > 1): ?>
                                    <a class="page-link" href="<?= BASE_URL ?>account?page=<?= $currentPage - 1 ?>" aria-label="Previous page">Previous</a>
                                <?php else: ?>
                                    <span class="page-link">Previous</span>
                                <?php endif; ?>
                            </li>
                            <?php for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++): ?>
                                <li class="page-item <?= $pageNumber === $currentPage ? 'active' : '' ?>">
                                    <?php if ($pageNumber === $currentPage): ?>
                                        <span class="page-link" aria-current="page"><?= $pageNumber ?></span>
                                    <?php else: ?>
                                        <a class="page-link" href="<?= BASE_URL ?>account?page=<?= $pageNumber ?>"><?= $pageNumber ?></a>
                                    <?php endif; ?>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $currentPage >= $pageCount ? 'disabled' : '' ?>">
                                <?php if ($currentPage < $pageCount): ?>
                                    <a class="page-link" href="<?= BASE_URL ?>account?page=<?= $currentPage + 1 ?>" aria-label="Next page">Next</a>
                                <?php else: ?>
                                    <span class="page-link">Next</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                        <p class="text-muted text-center small mt-2 mb-0">
                            Showing <?= min(($currentPage - 1) * $receiptsPerPage + 1, $receiptCount) ?>–<?= min($currentPage * $receiptsPerPage, $receiptCount) ?>
                            of <?= $receiptCount ?> <?= $receiptCount === 1 ? 'receipt' : 'receipts' ?>
                        </p>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require APP_VIEWS_PATH . '/checkout/no_js_footer.php'; ?>