<?php

require 'vendor/autoload.php';
use Kreait\Firebase\Configuration;
use Kreait\Firebase\Firebase;
use Kreait\Firebase\Query;

/** Functions required for various tasks the API needs to perform */
/**
 * Configure connection to firebase
 * @return Firebase
 */
function configureFireBase()
{
    $config = new Configuration();
    $config->setAuthConfigFile(__DIR__ . '/firebase-7055663f53c4.json');
    $firebase = new Firebase('https://upwork-test.firebaseio.com/', $config);
    return $firebase;
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoResponse($status_code, $response)
{
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

/**
 * Get user details by user id
 * @param $userId
 */
function getUserById($userId)
{
    $firebase = configureFireBase();
    $query = new Query();

    $query->orderByChildKey('user_id')->equalTo((int)$userId)->limitToFirst(1);
    $nodeGetContent = $firebase->users->query($query);
    if (count($nodeGetContent) > 0) {
        $nodeGetContent = array_values($nodeGetContent)[0];
        return $nodeGetContent;
    } else {
        return false;
    }
}

/**
 * Get order details as well as the respective user details by order id
 * @param $orderId
 * @return array|bool
 */
function getOrderById($orderId)
{
    $firebase = configureFireBase();
    $query = new Query();
    $query->orderByChildKey('order_id')->equalTo((int)$orderId)->limitToFirst(1);
    $nodeGetContent = $firebase->orders->query($query);
    if (count($nodeGetContent) > 0) {
        $nodeGetContent = array_values($nodeGetContent)[0];
        $userForOrder = getUserById($nodeGetContent['user_id']);
        unset($nodeGetContent['user_id']);
        $nodeGetContent = array_merge($nodeGetContent, ["user" => $userForOrder]);
        return $nodeGetContent;
    } else {
        return false;
    }
}

/**
 * Get order key by order id
 * @param $orderId
 * @return bool|mixed
 */
function getOrderKeyById($orderId)
{
    $firebase = configureFireBase();
    $query = new Query();
    $query->orderByChildKey('order_id')->equalTo((int)$orderId)->limitToFirst(1);
    $nodeGetContent = $firebase->orders->query($query);
    if (count($nodeGetContent) > 0) {
        $nodeGetContent = array_keys($nodeGetContent)[0];
        return $nodeGetContent;
    } else {
        return false;
    }
}

/**
 * Set order status to 2 (cancel order) by order id
 * @param $orderId
 * @return $this|bool
 */
function cancelOrderById($orderId)
{
    $firebase = configureFireBase();
    $orderKey = getOrderKeyById($orderId);
    if($orderKey) {
        $cancelStatus = $firebase->orders->$orderKey->update([
            'order_status' => 2
        ]);
        return $cancelStatus;
    } else {
        return false;
    }
}


/** Slim framework 2.x requests */

$app = new \Slim\Slim();

/** GET Request to get user by id */
$app->get('/getuser/userid/:userId', function ($userId) {
    $nodeGetContent = getUserById($userId);
    if ($nodeGetContent) {
        $response["error"] = false;
        $response["content"] = $nodeGetContent;
    } else {
        $response["error"] = true;
        $response["message"] = "The user does not exist.";
    }
    echoResponse(200, $response);
});

/** GET Request to get order by id */
$app->get('/getorder/orderid/:orderId', function ($orderId) {
    $nodeGetContent = getOrderById($orderId);
    if ($nodeGetContent) {
        $response["error"] = false;
        $response["content"] = $nodeGetContent;
        if ($nodeGetContent['order_status'] === 2) {
            $response["message"] = "The order has been cancelled.";
        }
    } else {
        $response["error"] = true;
        $response["message"] = "The order does not exist.";
    }
    echoResponse(200, $response);
});

/** PATCH Request to update order status by order id */
$app->patch('/cancelorder/orderid/:orderId', function ($orderId) {
    $cancelStatus = cancelOrderById($orderId);
    if($cancelStatus) {
        $response["error"] = false;
        $response["message"] = "Order successfully cancelled.";
    } else {
        $response["error"] = true;
        $response["message"] = "The order does not exist.";
    }

    echoResponse(200, $response);
});

/** Sample POST request just to fill in some user data */
$app->post('/users', function () {
    $config = new Configuration();
    $config->setAuthConfigFile(__DIR__ . '/upwork-test-7055663f53c4.json');

    $firebase = new Firebase('https://upwork-test.firebaseio.com/', $config);
    $firebase->users->push(["user_id" => 1,
        "first_name" => "Shailesh",
        "last_name" => "Singh",
        "email" => "ss@mat.com",
        "phone_no" => "+918896123344"]);
    $response["error"] = false;
    $response["message"] = "Created entry";
    echoResponse(201, $response);
});

/** Sample POST request just to fill in some order data */
$app->post('/orders', function () {
    $config = new Configuration();
    $config->setAuthConfigFile(__DIR__ . '/upwork-test-7055663f53c4.json');

    $firebase = new Firebase('https://upwork-test.firebaseio.com/', $config);
    $firebase->orders->push(["user_id" => 2,
        "order_id" => 2,
        "order_total" => "500",
        "order_date" => "2016-07-19",
        "order_status" => 1]);
    $response["error"] = false;
    $response["message"] = "Created entry";
    echoResponse(201, $response);
});

$app->run();