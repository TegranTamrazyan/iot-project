<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CheckoutModel;
use App\Helpers\CheckoutSecurity as Security;
use App\Helpers\Core\FlashMessage;
use App\Helpers\EmailHelper;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Throwable;

class CheckoutController extends BaseController
{
    public function __construct(Container $container,private CheckoutModel $model)
    { parent::__construct($container); }

    private function go(Response $r,string $path): Response
    { return $r->withStatus(303)->withHeader('Location',BASE_URL.$path); }

    private function validForm(Request $request,Response $response): ?Response
    {
        if (Security::validCsrf($request)) return null;
        $response->getBody()->write('Invalid form token. Refresh the page and retry.');
        return $response->withStatus(403);
    }

    public function index(Request $request,Response $response): Response
    {
        $cart=$this->model->cart();
        $live=($request->getQueryParams()['live']??'1')!=='0';
        $response=$this->render($response,'checkout/index.php',[
            'groups'=>$this->model->groupedCart($cart), 'cart'=>$cart,
            'removed'=>$this->model->removedTags(), 'live'=>$live,
            'error'=>(string)($request->getQueryParams()['error']??''),
        ]+$this->model->totals($cart));
        // Plain HTTP page refresh. No JavaScript is needed for scanner updates.
        return $live ? $response->withHeader('Refresh','3; url='.BASE_URL.'checkout?live=1') : $response;
    }

    public function add(Request $request,Response $response): Response
    {
        if ($bad=$this->validForm($request,$response)) return $bad;
        $data=(array)($request->getParsedBody()??[]);
        try {
            if (!empty($data['upc'])) $this->model->addUpc(trim((string)$data['upc']));
            elseif (!empty($data['epcs'])) {
                $epcs=preg_split('/[\s,;]+/',(string)$data['epcs'],-1,PREG_SPLIT_NO_EMPTY);
                if (count($epcs)>1000) throw new RuntimeException('Too many EPCs in one batch.');
                $result=$this->model->addEpcs($epcs);
                if ($result['unknown']) throw new RuntimeException('Unknown EPC(s): '.implode(', ',array_slice($result['unknown'],0,5)));
            } else throw new RuntimeException('Enter a barcode UPC or EPC tags.');
            return $this->go($response,'checkout?live=0');
        } catch (Throwable $e) { return $this->go($response,'checkout?live=0&error='.rawurlencode($e->getMessage())); }
    }

    public function remove(Request $request,Response $response): Response
    {
        if ($bad=$this->validForm($request,$response)) return $bad;
        $data=(array)($request->getParsedBody()??[]);
        $this->model->remove((int)($data['id']??0));
        return $this->go($response,'checkout?live=0');
    }

    public function clear(Request $request,Response $response): Response
    {
        if ($bad=$this->validForm($request,$response)) return $bad;
        $this->model->clear();
        return $this->go($response,'checkout?live=0');
    }

    public function restoreTag(Request $request,Response $response): Response
    {
        if ($bad=$this->validForm($request,$response)) return $bad;
        $body=(array)($request->getParsedBody()??[]);
        $this->model->restoreTag(strtoupper(trim((string)($body['epc']??''))));
        return $this->go($response,'checkout?live=0');
    }

    public function confirm(Request $request,Response $response): Response
    {
        $cart=$this->model->cart();
        if (!$cart) return $this->go($response,'checkout?live=0');
        $_SESSION['checkout_review_hash']=$this->model->cartFingerprint();
        $customer=null;
        if (!empty($_SESSION['customer_id'])) $customer=$this->model->memberById((int)$_SESSION['customer_id']);
        return $this->render($response,'checkout/confirm.php',[
            'groups'=>$this->model->groupedCart($cart), 'customer'=>$customer,
            'error'=>(string)($request->getQueryParams()['error']??''),
        ]+$this->model->totals($cart));
    }

    public function pay(Request $request,Response $response): Response
    {
        if ($bad=$this->validForm($request,$response)) return $bad;
        $body=(array)($request->getParsedBody()??[]);
        try {
            if (empty($_SESSION['checkout_review_hash']) ||
                !hash_equals((string)$_SESSION['checkout_review_hash'],$this->model->cartFingerprint())) {
                throw new RuntimeException('The cart changed after review. Please check it and confirm again.');
            }
            $choice=(string)($body['membership_choice']??'guest');
            $customerId=null;
            if ($choice==='account') {
                if (empty($_SESSION['customer_id'])) throw new RuntimeException('Sign in before using your account.');
                $customerId=(int)$_SESSION['customer_id'];
            } elseif ($choice==='card') {
                $member=$this->model->memberByCode(trim((string)($body['membership_code']??'')));
                if (!$member) throw new RuntimeException('Membership number not found.');
                $customerId=(int)$member['customer_id'];
            } elseif ($choice!=='guest') throw new RuntimeException('Invalid membership selection.');
            $id=$this->model->pay($customerId);
            unset($_SESSION['checkout_review_hash']);
            $_SESSION['last_checkout_order']=$id;

            if ($customerId !== null) {
                try {
                    $customer = $this->model->memberById($customerId);
                    $order = $this->model->orderForSuccess($id);
                    if ($customer && $order && filter_var($customer['email_address'] ?? '', FILTER_VALIDATE_EMAIL)) {
                        $items = $this->model->orderItems($id);
                        $receipt = EmailHelper::buildReceiptHtml($order, $items);
                        if (EmailHelper::sendReceipt((string)$customer['email_address'], $receipt)) {
                            FlashMessage::success('Receipt to Email sent successfully. Check your inbox or spam folder.');
                        } else {
                            FlashMessage::error('Purchase complete, but we couldn’t send your receipt. Please check the email address or try again later.');
                        }
                    } else {
                        FlashMessage::error('Purchase complete, but no valid email address is available for this account.');
                    }
                } catch (Throwable $emailError) {
                    error_log('Receipt email error: ' . $emailError->getMessage());
                    FlashMessage::error('Purchase complete, but we couldn’t send your receipt. Please check the email address or try again later.');
                }
            }

            return $this->go($response,'checkout/done');
        } catch(Throwable $e) { return $this->go($response,'checkout/confirm?error='.rawurlencode($e->getMessage())); }
    }

    public function done(Request $request, Response $response): Response
    {
        $id = (int)($_SESSION['last_checkout_order'] ?? 0);

        if (!$id) {
            return $this->go($response, 'checkout');
        }
        $order = $this->model->orderForSuccess($id);
        if (!$order) {
            return $this->go($response, 'checkout');
        }

        $items = $this->model->orderItems($id);
        $customer = null;
        if (!empty($order['customer_id'])) {
            $customer = $this->model->memberById((int)$order['customer_id']);
        }

        return $this->render($response, 'checkout/done.php', [
            'order' => $order,
            'items' => $items,
            'customer' => $customer,
            'purchases' => [$order],
            'purchaseItems' => [(int)$order['id'] => $items],
        ]);
    }

    public function emailReceipt(Request $request, Response $response): Response
    {
        if ($bad = $this->validForm($request, $response)) {
            return $bad;
        }

        $body = (array) ($request->getParsedBody() ?? []);
        $orderId = (int) ($body['order_id'] ?? 0);
        $email = trim((string) ($body['receipt_email'] ?? ''));
        $lastOrderId = (int) ($_SESSION['last_checkout_order'] ?? 0);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            FlashMessage::error('Please enter a valid email address.');
            return $this->go($response, 'checkout/done');
        }

        if ($orderId < 1 || $orderId !== $lastOrderId) {
            FlashMessage::error('That receipt is no longer available in this checkout session.');
            return $this->go($response, 'checkout');
        }

        $order = $this->model->orderForSuccess($orderId);
        if (!$order) {
            FlashMessage::error('The receipt could not be found.');
            return $this->go($response, 'checkout');
        }

        $items = $this->model->orderItems($orderId);
        $receipt = EmailHelper::buildReceiptHtml($order, $items);
        if (!EmailHelper::sendReceipt($email, $receipt)) {
            FlashMessage::error('Receipt email could not be sent. Check the SMTP configuration and try again.');
            return $this->go($response, 'checkout/done');
        }

        FlashMessage::success('email sent successful');
        return $this->go($response, 'checkout/done');
    }

    /** Local scanner bridge: a UPC or many EPCs in a single JSON request. */
    public function scanApi(Request $request,Response $response): Response
    {
        $key=(string)(getenv('CHECKOUT_SCAN_KEY')?:'');
        if ($key==='' || !hash_equals($key,$request->getHeaderLine('X-Checkout-Key')))
            return $this->json($response,['error'=>'Unauthorized'],401);
        $data=json_decode((string)$request->getBody(),true);
        if (!is_array($data)) return $this->json($response,['error'=>'Invalid JSON'],400);
        try {
            if (isset($data['upc']) && is_string($data['upc'])) {
                $this->model->addUpc(trim($data['upc'])); $result=['added'=>1];
            } elseif (isset($data['epcs']) && is_array($data['epcs'])) {
                if (count($data['epcs'])>1000) return $this->json($response,['error'=>'Too many EPCs'],400);
                $result=$this->model->addEpcs($data['epcs']);
            } else return $this->json($response,['error'=>'Expected upc or epcs'],400);
            return $this->json($response,$result);
        } catch(Throwable $e) { return $this->json($response,['error'=>$e->getMessage()],400); }
    }

    private function json(Response $response,array $data,int $status=200): Response
    {
        $response->getBody()->write(json_encode($data,JSON_UNESCAPED_SLASHES));
        return $response->withStatus($status)->withHeader('Content-Type','application/json');
    }
}
