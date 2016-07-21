# firebase-php-example
Sample to connect to firebase using Slim Framework 2.x

Note that firebase-sample-db.json is nothing but a sample database that is being used at firebase. It just to show the structure and schema of the database currently being used at firebase for this API sample.

#Install
1. Clone or download the project.
2. Run composer install.

#What does this sample do
Perform the following requests:

• /getuser/userid/:userid
Get user by id

• /getorder/orderid/:orderid
Get order details with user details for the order givein by order id

• /cancelorder/orderId/:orderid
Mark order status 2 (cancel order) for a given order by id

Basic error handling has also been done.