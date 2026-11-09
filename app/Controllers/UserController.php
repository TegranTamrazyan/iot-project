<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\UserModel;
use App\Helpers\CheckoutSecurity as Security;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Throwable;

class UserController extends BaseController
{
    public function __construct(Container $container,private UserModel $users)
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

    public function login(Request $req,Response $res): Response
    {
        if ($bad=$this->csrfFailure($req,$res)) return $bad;
        $data=(array)($req->getParsedBody()??[]);
        $email=strtolower(trim((string)($data['email_address']??'')));
        $password=(string)($data['password']??'');
        $user=$this->users->getUserByEmail($email);
        if (!$user || !password_verify($password,(string)$user['password_hash']))
            return $this->go($res,'login?error='.rawurlencode('Incorrect email or password.'));
        session_regenerate_id(true);
        unset($_SESSION['last_checkout_order'],$_SESSION['checkout_review_hash']);
        $_SESSION['customer_id']=(int)$user['customer_id'];
        return $this->go($res,'account');
    }

    public function account(Request $req,Response $res): Response
    {
        $id=(int)($_SESSION['customer_id']??0);
        if (!$id) return $this->go($res,'login');
        $customer=$this->users->getUserById($id);
        if (!$customer) { unset($_SESSION['customer_id']); return $this->go($res,'login'); }
        $items=[];
        foreach ($this->users->purchasedItems($id) as $item) {
            $items[(int)$item['order_id']][]=$item;
        }
        return $this->render($res,'account/index.php',[
            'customer'=>$customer,'purchases'=>$this->users->purchases($id),'purchaseItems'=>$items]);
    }

    public function logout(Request $req,Response $res): Response
    {
        if ($bad=$this->csrfFailure($req,$res)) return $bad;
        unset($_SESSION['customer_id'], $_SESSION['last_checkout_order'],$_SESSION['checkout_review_hash']);
        session_regenerate_id(true);
        return $this->go($res,'checkout');
    }
}
