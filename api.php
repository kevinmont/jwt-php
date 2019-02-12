<?php

include_once 'jwt.php';
include_once 'customer.php';

class Api extends Rest
{
    public $dbConnection;
    public function __construct()
    {
        parent::__construct();
        $db = new DbConnect();
        $this->dbConnection = $db->connect();
    }

    public function generateToken()
    {
        try {
            $email = $this->validateParameters('email', $this->param['email'], STRING);
            $password = $this->validateParameters('password', $this->param['password'], STRING);

            $stmt = $this->dbConnection->prepare("SELECT * FROM users WHERE email = :email AND password = :password");
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $password);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($user)) {
                $this->returnResponse(INVALID_USER_PASSWORD, "Email or Password are incorrect.");
            }

            if ($user['active'] === 0) {
                $this->returnResponse(USER_NOT_ACTIVE, "User is not actived. Please contact to admin.");
            }

            $payload = [
                'iat' => time(),
                'iss' => 'localhost',
                'exp' => time() + (60 * 30),
                'userId' => $user['id'],
            ];

            $token = JWT::encode($payload, SECRET_KEY);
            $data = ['token' => $token];
            $this->returnResponse(SUCCESS_RESPONSE, $data);
        } catch (Exception $e) {
            $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());
        }
    }

    public function addCustomer()
    {
        $name = $this->validateParameters('name', $this->param['name'], STRING, false);
        $email = $this->validateParameters('email', $this->param['email'], STRING);
        $address = $this->validateParameters('address', $this->param['address'], STRING, false);
        $mobile = $this->validateParameters('mobile', $this->param['mobile'], INTEGER, false);

        $customer = new Customer();
        $customer->setName($name);
        $customer->setMobile($mobile);
        $customer->setAddress($address);
        $customer->setEmail($email);
        $customer->setCreatedBy($this->userId);
        $customer->setUpdatedBy($this->userId);
        $customer->setCreatedOn(date('Y-m-d'));
        $customer->setUpdatedOn(date('Y-m-d'));

        if (!$customer->insertCustomer()) {
            $message = 'Failed to insert';
        } else {
            $message = 'Inserted successfully.';
        }
        $this->returnResponse(SUCCESS_RESPONSE, $message);
    }

    public function getCustomerDatails()
    {
        $customerId = $this->validateParameters('customerId', $this->param['customerId'], INTEGER);

        $cust = new Customer();
        $cust->setId($customerId);
        $customer = $cust->getCustomerDetailsById();

        if (!is_array($customer)) {
            $this->returnResponse(SUCCESS_RESPONSE, ['message' => 'Customer details are not in database']);
        }

        $response['customerId'] = $customer['id'];
        $response['name'] = $customer['name'];
        $response['email'] = $customer['email'];
        $response['address'] = $customer['address'];
        $response['mobile'] = $customer['mobile'];
        $response['createdBy'] = $customer['created_user'];
        $response['lastUpdatedBy'] = $customer['updated_user'];
        $this->returnResponse(SUCCESS_RESPONSE, $response);
    }

    public function updateCustomer()
    {
        $customerId = $this->validateParameters('customerId', $this->param['customerId'], INTEGER, false);
        $name = $this->validateParameters('name', $this->param['name'], STRING);
        $address = $this->validateParameters('address', $this->param['address'], STRING, false);
        $mobile = $this->validateParameters('mobile', $this->param['mobile'], INTEGER, false);

        $customer = new Customer();
        $customer->setId($customerId);
        $customer->setName($name);
        $customer->setMobile($mobile);
        $customer->setAddress($address);
        //$customer->setCreatedBy($this->userId);
        $customer->setUpdatedBy($this->userId);
        //$customer->setCreatedOn(date('Y-m-d'));
        $customer->setUpdatedOn(date('Y-m-d'));

        if (!$customer->updateCustomer()) {
            $message = 'Failed to update';
        } else {
            $message = 'Updated successfully.';
        }
        $this->returnResponse(SUCCESS_RESPONSE, $message);
    }

    public function deleteCustomer()
    {
        $customerId = $this->validateParameters('customerId', $this->param['customerId'], INTEGER);

        $cust = new Customer();
        $cust->setId($customerId);

        if (!$cust->deleteCustomer()) {
            $message = "Failed to delete";
        } else {
            $message = "Deleted successfully.";
        }

        $this->returnResponse(SUCCESS_RESPONSE, $message);
    }

}
