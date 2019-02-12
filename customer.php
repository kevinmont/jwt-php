<?php

include_once 'db-connect.php';

class Customer
{
    private $id;
    private $name;
    private $mobile;
    private $email;
    private $address;
    private $createdBy;
    private $createdOn;
    private $updatedBy;
    private $updatedOn;

    private $db;

    public function setId($id)
    {$this->id = $id;}
    public function getId()
    {return $this->id;}

    public function setName($name)
    {$this->name = $name;}
    public function getName()
    {return $this->name;}

    public function setMobile($mobile)
    {$this->mobile = $mobile;}
    public function getMobile()
    {return $this->mobile;}

    public function setEmail($email)
    {$this->email = $email;}
    public function getEmail()
    {return $this->email;}

    public function setAddress($address)
    {$this->address = $address;}
    public function getAddress()
    {return $this->address;}

    public function setCreatedBy($createdBy)
    {$this->createdBy = $createdBy;}
    public function getCreatedBy()
    {return $this->createdBy;}

    public function setUpdatedBy($updatedBy)
    {$this->updatedBy = $updatedBy;}
    public function getUpdatedBy()
    {return $this->updatedBy;}

    public function setCreatedOn($createdOn)
    {$this->createdOn = $createdOn;}
    public function getCreatedOn()
    {return $this->createdOn;}

    public function setUpdatedOn($updatedOn)
    {$this->updatedOn = $updatedOn;}
    public function getUpdatedOn()
    {return $this->updatedOn;}

    public function __construct()
    {
        $db = new DbConnect();
        $this->db = $db->connect();
    }

    public function getAllCustomers()
    {
        try {
            $statement = $this->db->prepare("SELECT * FROM customers");
            $statement->execute();
            $customers = $statement->fetchAll(PDO::FETCH_ASSOC);
            return $customers;
        } catch (Exception $e) {
            echo "Error";
        }
    }

    public function getCustomerDetailsById()
    {
        try {
            $query = "SELECT
                            c.*,
                            u.name as created_user,
                            u1.name as updated_user
                    FROM customers c JOIN users u ON (c.created_by = u.id)
                        LEFT JOIN users u1 ON (c.updated_by = u1.id)
                    WHERE c.id= :customerId";
            $statement = $this->db->prepare($query);
            $statement->bindParam(":customerId", $this->id);
            $statement->execute();
            $customer = $statement->fetch(PDO::FETCH_ASSOC);
            return $customer;
        } catch (Exception $e) {
            echo "Error";
        }
    }

    public function insertCustomer()
    {
        try {
            $statement = $this->db->prepare("INSERT INTO customers(name, mobile, email,
                 address, created_by, updated_by, created_on, updated_on)
                 VALUES (:name, :mobile, :email, :address, :createdBy,
                 :updatedBy, :createdOn, :updatedOn)");

            $statement->bindParam(":name", $this->name);
            $statement->bindParam(":mobile", $this->mobile);
            $statement->bindParam(":email", $this->email);
            $statement->bindParam(":address", $this->address);
            $statement->bindParam(":createdBy", $this->createdBy);
            $statement->bindParam(":updatedBy", $this->updatedBy);
            $statement->bindParam(":createdOn", $this->createdOn);
            $statement->bindParam(":updatedOn", $this->updatedOn);
            $result = $statement->execute();
            if ($result) {
                return true;
            } else {
                return false;
            }

        } catch (Exception $e) {
            echo "Database error:" . $e->getMessage();
        }
    }

    public function deleteCustomer()
    {
        $statement = $this->db->prepare("DELETE FROM customers WHERE id=:userId");
        $statement->bindParam(":userId", $this->id);

        if ($statement->execute()) {
            return true;
        } else {
            return false;
        }

    }

    public function updateCustomer()
    {
        $query = "UPDATE customers SET ";

        if ($this->getMobile() != null) {
            $query .= "mobile='" . $this->mobile . "', ";
        }
        if ($this->getName() != null) {
            $query .= "name='" . $this->name . "', ";
        }

        if ($this->getAddress() != null) {
            $query .= "address='" . $this->address . "', ";
        }

        $query .= " updated_by= :updatedBy, updated_on= :updatedOn
                    WHERE id= :userId;";

        $statement = $this->db->prepare($query);
        $statement->bindParam(":updatedBy", $this->updatedBy);
        $statement->bindParam(":updatedOn", $this->updatedOn);
        $statement->bindParam(":userId", $this->id);

        if ($statement->execute()) {
            return true;
        } else {
            return false;
        }
    }

}
