<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CheckoutModel;
use App\Domain\Models\UserModel;
use App\Helpers\CheckoutSecurity as Security;
use App\Helpers\Core\FlashMessage;
use App\Helpers\EmailHelper;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Throwable;

class UserController extends BaseController
{
    public function __construct(Container $container,private UserModel $users,private CheckoutModel $checkout)
    { parent::__construct($container); }

    private function go(Response $r,string $path): Response
    { return $r->withStatus(303)->withHeader('Location',BASE_URL.$path); }

    private function csrfFailure(Request $req,Response $res): ?Response
    {
        if (Security::validCsrf($req)) return null;
        $res->getBody()->write('Invalid form token. Please refresh.');
        return $res->withStatus(403);
    }

    public function view_adding_user(Request $req,Response $res,array $args=[]): Response
    {
        return $this->render($res,'addingUser_view.php',[
            'error'=>(string)($req->getQueryParams()['error']??'')]);
    }

    public function store_user(Request $req,Response $res,array $args=[]): Response
    {
        if ($bad=$this->csrfFailure($req,$res)) return $bad;
        $data=(array)($req->getParsedBody()??[]);
        $name=trim((string)($data['customer_name']??''));
        $email=strtolower(trim((string)($data['email_address']??'')));
        $password=(string)($data['password']??'');
        $confirm=(string)($data['password_confirm']??'');
        try {
            if ($password!==$confirm) throw new RuntimeException('Passwords do not match.');
            $id=$this->users->createCustomer($name,$email,$password,
                trim((string)($data['telephone_number']??'')),
                trim((string)($data['address']??'')));
            session_regenerate_id(true);
            unset($_SESSION['last_checkout_order'],$_SESSION['checkout_review_hash']);
            $_SESSION['customer_id']=$id;
            return $this->go($res,'account');
        } catch(Throwable $e) {
            return $this->go($res,'adding?error='.rawurlencode($e->getMessage()));
        }
    }

    public function view_login_user(Request $req,Response $res,array $args=[]): Response
    {
        return $this->render($res,'loginUserView.php',[
            'error'=>(string)($req->getQueryParams()['error']??'')]);
    }

    public function login(Request $req, Response $res): Response
    {
        if ($bad = $this->csrfFailure($req, $res)) return $bad;
        
        $data = $req->getParsedBody();
        $email = strtolower(trim(($data['email_address'] ?? '')));
        $password = $data['password'] ?? '';

        $user = $this->users->getUserByEmail($email);
        if (!$user || !password_verify($password,$user['password_hash'])) {
            // return $this->go($res,'login?error='.rawurlencode('Incorrect email or password.'));
            FlashMessage::error('Incorrect email or password.');
            return $this->go($res,'login');
        }
        session_regenerate_id(true);

        unset($_SESSION['last_checkout_order'], $_SESSION['checkout_review_hash']);
        
        $_SESSION['customer_id'] = $user['customer_id'];
        $_SESSION['is_logged_in'] = true;
        $_SESSION['is_admin'] = $user['is_admin'] > 0;
    
        return $this->go($res, 'account');
    }

    public function account(Request $req,Response $res): Response
    {
        $id=(int)($_SESSION['customer_id']??0);
        if (!$id) return $this->go($res,'login');
        $customer=$this->users->getUserById($id);
        if (!$customer) { unset($_SESSION['customer_id']); return $this->go($res,'login'); }

        $receiptsPerPage = 10;
        $receiptCount = $this->users->purchaseCount($id);
        $pageCount = max(1, (int)ceil($receiptCount / $receiptsPerPage));
        $requestedPage = filter_var($req->getQueryParams()['page'] ?? 1, FILTER_VALIDATE_INT);
        $page = min($pageCount, max(1, $requestedPage === false ? 1 : $requestedPage));
        $purchases = $this->users->purchases(
            $id,
            limit: $receiptsPerPage,
            offset: ($page - 1) * $receiptsPerPage
        );
        $items = [];
        $orderIds = array_map(static fn(array $order): int => (int)$order['id'], $purchases);
        foreach ($this->users->purchasedItems($id, $orderIds) as $item) {
            $items[(int)$item['order_id']][] = $item;
        }

        return $this->render($res,'account/index.php',[
            'customer'=>$customer,
            'purchases'=>$purchases,
            'purchaseItems'=>$items,
            'currentPage'=>$page,
            'pageCount'=>$pageCount,
            'receiptCount'=>$receiptCount,
            'receiptsPerPage'=>$receiptsPerPage,
        ]);
    }

    public function purchaseReport(Request $req,Response $res): Response
    {
        $id=(int)($_SESSION['customer_id']??0);
        if (!$id) return $this->go($res,'login');
        $customer=$this->users->getUserById($id);
        if (!$customer) { unset($_SESSION['customer_id']); return $this->go($res,'login'); }

        $query = $req->getQueryParams();
        $fromDate = trim((string)($query['from'] ?? ''));
        $toDate = trim((string)($query['to'] ?? ''));
        $search = trim((string)($query['item'] ?? ''));
        $productRowsPerPage = 10;
        $reportError = '';
        foreach (['Start date' => $fromDate, 'End date' => $toDate] as $label => $date) {
            if ($date !== '' && !$this->isReportDate($date)) {
                $reportError = $label . ' must be a valid date.';
                break;
            }
        }
        if ($reportError === '' && $fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
            $reportError = 'Start date must be on or before end date.';
        }
        if ($reportError === '' && strlen($search) > 100) {
            $reportError = 'Item search must be 100 characters or fewer.';
        }

        $purchases = $reportError === '' ? $this->users->purchases(
            $id,
            $fromDate !== '' ? $fromDate : null,
            $toDate !== '' ? $toDate : null
        ) : [];
        $productRowsCount = $reportError === '' ? $this->users->purchasedProductRowCount(
            $id,
            $fromDate !== '' ? $fromDate : null,
            $toDate !== '' ? $toDate : null
        ) : 0;
        $productRowsPageCount = max(1, (int)ceil($productRowsCount / $productRowsPerPage));
        $requestedProductRowsPage = filter_var($query['products_page'] ?? 1, FILTER_VALIDATE_INT);
        $productRowsPage = min(
            $productRowsPageCount,
            max(1, $requestedProductRowsPage === false ? 1 : $requestedProductRowsPage)
        );
        $productRows = $reportError === '' ? $this->users->purchasedProductRows(
            $id,
            $fromDate !== '' ? $fromDate : null,
            $toDate !== '' ? $toDate : null,
            $productRowsPerPage,
            ($productRowsPage - 1) * $productRowsPerPage
        ) : [];
        $productPurchases = $reportError === '' && $search !== ''
            ? $this->users->productPurchases(
                $id,
                $search,
                $fromDate !== '' ? $fromDate : null,
                $toDate !== '' ? $toDate : null
            )
            : [];
        $purchasedProductNames = $reportError === ''
            ? $this->users->purchasedProductNames(
                $id,
                $fromDate !== '' ? $fromDate : null,
                $toDate !== '' ? $toDate : null
            )
            : [];
        $totalSpent = array_sum(array_map(
            static fn(array $order): float => (float)$order['total'],
            $purchases
        ));
        $spendingByDate = [];
        foreach ($purchases as $purchase) {
            $date = substr((string)$purchase['created_at'], 0, 10);
            $spendingByDate[$date] = ($spendingByDate[$date] ?? 0.0) + (float)$purchase['total'];
        }
        ksort($spendingByDate);

        return $this->render($res,'account/report.php',[
            'customer'=>$customer,
            'purchases'=>$purchases,
            'totalSpent'=>$totalSpent,
            'spendingByDate'=>$spendingByDate,
            'productRows'=>$productRows,
            'productRowsCount'=>$productRowsCount,
            'productRowsPage'=>$productRowsPage,
            'productRowsPageCount'=>$productRowsPageCount,
            'productRowsPerPage'=>$productRowsPerPage,
            'productSearch'=>$search,
            'productPurchases'=>$productPurchases,
            'purchasedProductNames'=>$purchasedProductNames,
            'reportError'=>$reportError,
            'fromDate'=>$fromDate,
            'toDate'=>$toDate,
        ]);
    }

    private function isReportDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false
            && $parsed->format('Y-m-d') === $date;
    }

    public function logout(Request $req,Response $res): Response
    {
        if ($bad=$this->csrfFailure($req,$res)) return $bad;
        unset($_SESSION['customer_id'], $_SESSION['last_checkout_order'],$_SESSION['checkout_review_hash'], $_SESSION['customer_id'], $_SESSION['is_admin']);

        session_regenerate_id(true);
        return $this->go($res,'checkout');
    }

    public function resendReceipt(
        Request $request,
        Response $response
    ): Response {
        // Check CSRF token
        if ($bad = $this->csrfFailure($request, $response)) {
            return $bad;
        }

        // Get submitted information
        $body = (array)($request->getParsedBody() ?? []);

        $orderId = (int)($body['order_id'] ?? 0);
        $email = trim((string)($body['receipt_email'] ?? ''));

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            FlashMessage::error('Please enter a valid email address.');
            return $this->go($response, 'account');
        }

        // Get logged-in customer
        $customerId = (int)($_SESSION['customer_id'] ?? 0);

        // Verify order belongs to customer
        $order = $this->checkout->orderForSuccess($orderId);

        if (!$order || (int)$order['customer_id'] !== $customerId) {
            FlashMessage::error('That order does not belong to your account.');
            return $this->go($response, 'account');
        }

        // Retrieve original purchased items
        $items = $this->checkout->orderItems($orderId);

        $receiptHtml = EmailHelper::buildReceiptHtml($order, $items);

        // Send email
        $sent = EmailHelper::sendReceipt($email, $receiptHtml);

        if (!$sent) {
            FlashMessage::error('Receipt could not be sent. Please try again.');
            return $this->go($response, 'account');
        }

        FlashMessage::success('Receipt sent successfully.');
        return $this->go($response, 'account');
    }    
}
