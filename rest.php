<?php

require_once 'constant.php';
require_once 'db-connect.php';

class Rest
{
    protected $request;
    protected $serviceName;
    protected $param;
    protected $dbConnection;
    protected $userId;

    public function __construct()
    {
        if (($_SERVER['REQUEST_METHOD'] !== 'POST')) {
            echo $_SERVER['REQUEST_METHOD'];
            $this->throwError(REQUEST_METHOD_NOT_VALID, 'Request Method is not valid');
        }
        $handler = fopen('php://input', 'r');
        $this->request = stream_get_contents($handler);
        $this->validateRequest();

        $db = new DbConnect();
        $this->dbConnection = $db->connect();

        if ('generatetoken' != strtolower($this->serviceName)) {
            $this->validateToken();
        }

    }

    public function validateRequest()
    {
        if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
            $this->throwError(REQUEST_CONTENT_TYPE_NOT_VALID, 'Request content type is not valid');
        }
        $data = json_decode($this->request, true);

        if (!isset($data['name']) || $data['name'] == "") {
            $this->throwError(API_NAME_REQUIRED, "Api name required");
        }
        $this->serviceName = $data['name'];

        if (!is_array($data['param'])) {
            $this->throwError(API_PARAM_REQUIRED, "Api param required");
        }
        $this->param = $data['param'];
    }

    public function processApi()
    {
        $api = new Api();
        $rMethod = new ReflectionMethod($api, $this->serviceName);
        if (!method_exists($api, $this->serviceName)) {
            $this->throwError(API_DOES_NOT_EXIST, "API does not exist");
        }
        $rMethod->invoke($api);
    }

    public function validateParameters($fieldName, $value, $dataType, $required = true)
    {
        if ($required && empty($value)) {
            $this->throwError(VALIDATE_PARAMETER_REQUIRED, $fieldName . " parameter is required.");
        }

        switch ($dataType) {
            case BOOLEAN:
                if (!is_bool($value)) {
                    $this->throwError(VALIDATE_PARAMETER_DATATYPE,
                        "Datatype is not valid for " . $fieldName . ". It should be boolean.");
                }
                break;
            case INTEGER:
                if (!is_numeric($value)) {
                    $this->throwError(VALIDATE_PARAMETER_DATATYPE,
                        "Datatype is not valid for " . $fieldName . ". It should be numeric.");
                }
                break;
            case STRING:
                if (!is_string($value)) {
                    $this->throwError(VALIDATE_PARAMETER_DATATYPE,
                        "Datatype is not valid for " . $fieldName . ". It should be string.");
                }
                break;
            default:
                $this->throwError(VALIDATE_PARAMETER_DATATYPE,
                    "Datatype is not valid for " . $fieldName);
                break;
        }
        return $value;
    }

    public function throwError($code, $message)
    {
        header("Content-type: application/json");
        $errorRes = json_encode(['error' => ['status' => $code, 'message' => $message]]);
        echo $errorRes;exit;
    }

    public function returnResponse($code, $data)
    {
        header("Content-type:application/json");
        $response = json_encode(['response' => ['status' => $code, 'result' => $data]]);
        echo $response;exit;
    }

    public function getAuthorizationHeaders()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();

            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)), array_values(
                    $requestHeaders
                )
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    public function getBearerToken()
    {
        $headers = $this->getAuthorizationHeaders();

        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        $this->throwError(AUTHORIZATION_HEADER_NOT_FOUND, "Access Token not found");
    }

    public function validateToken()
    {
        try {
            $token = $this->getBearerToken();
            $payload = JWT::decode($token, SECRET_KEY, ['HS256']);

            $stmt = $this->dbConnection->prepare("SELECT * FROM users WHERE id =:userId");
            $stmt->bindParam(":userId", $payload->userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($user)) {
                $this->returnResponse(INVALID_USER_PASSWORD, "This user was not found in database.");
            }

            if ($user['active'] == 0) {
                $this->returnResponse(USER_NOT_ACTIVE, "This user is not actived. Please contact to admin.");
            }
            $this->userId = $payload->userId;
        } catch (Exception $e) {
            $this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
        }
    }
}
