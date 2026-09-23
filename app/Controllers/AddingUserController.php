<?php

namespace App\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Domain\Models\AddingUserModel;

class AddingUserController extends BaseController
{
    public function __construct(
        Container $container,
        private AddingUserModel $addingUserModel
    ) {
        parent::__construct($container);
    }

    // Display the add customer page
    public function view_adding_user(Request $request,Response $response,array $args): Response {
        return $this->render($response,'addingUser_view.php');
    }

    // Insert a new customer
    public function store_user(Request $request,Response $response,array $args): Response {

        $data = $request->getParsedBody();

        $customerName = trim($data['customer_name'] ?? '');
        $email = trim($data['email_address'] ?? '');
        $phoneNumber = trim($data['telephone_number'] ?? '');
        $address = trim($data['address'] ?? '');

        // Check required fields
        if (
            empty($customerName) ||
            empty($email) ||
            empty($phoneNumber) ||
            empty($address)
        ) {
            $_SESSION['error'] = "All fields are required.";


            return $this->redirect(
                $request,
                $response,
                'adding.index'
            );
        }

        // Validate customer name
        if (!preg_match('/^[a-zA-Z\s]+$/', $customerName)) {

            $_SESSION['error'] =
                "Customer name cannot contain numbers or symbols.";

            return $this->redirect(
                $request,
                $response,
                'adding.index'
            );
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $_SESSION['error'] =
                "Please enter a valid email address.";


            return $this->redirect(
                $request,
                $response,
                'adding.index'
            );
        }

        // Check if email already exists
        if ($this->addingUserModel->emailExists($email)) {

            $_SESSION['error'] =
                "A customer with this email already exists.";


            return $this->redirect(
                $request,
                $response,
                'adding.index'
            );
        }

        // Check if name already exists
        if ($this->addingUserModel->nameExists($customerName)) {

            $_SESSION['error'] =
                "A customer with this name already exists.";
            
            return $this->redirect(
                $request,
                $response,
                'adding.index'
            );
        }

        // Check if phone number already exists
        if ($this->addingUserModel->phoneNumberExists($phoneNumber)) {

            $_SESSION['error'] =
                "A customer with this phone number already exists.";

            return $this->redirect(
                $request,
                $response,
                'adding.index'
            );
        }

        if (!preg_match('/^\d{10}$/', $phoneNumber)) {

            $_SESSION['error'] =
                "A customer can't have a phone number of this size or of this format. e.g: 1231231234";

            return $this->redirect(
                $request,
                $response,
                'adding.index'
            );
        }

        // Insert customer
        $this->addingUserModel->customerInsertion([
            'customer_name' => $customerName,
            'address' => $address,
            'telephone_number' => $phoneNumber,
            'email_address' => $email
        ]);

        $_SESSION['success'] = "Customer added successfully.";


        return $this->redirect(
            $request,
            $response,
            'adding.index'
        );
    }
}

