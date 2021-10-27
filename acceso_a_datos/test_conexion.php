<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET,POST,PUT");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__.'/../classes/Database.php';
require __DIR__.'/../middlewares/Auth.php';

$allHeaders = getallheaders();
$db_connection = new Database();
$conn = $db_connection->dbConnection();
$auth = new Auth($conn, $allHeaders);

$returnData = [
    "success" => 0,
    "status" => 401,
    "message" => "No autorizado"
];

if(true){

    function msg($success, $status, $message, $extra = []){
        return [
            'success' => $success,
            'status' => $status,
            'message' => $message,
            'data' => $extra];
    }

    // Array de retorno de resultado
    $returnData = [];
    
    // Admitir solo POST
    //if($_SERVER["REQUEST_METHOD"] != "POST"):
    //    $returnData = msg(0, 404, 'La página solicitada no existe!');

    //else:

        try{
            $returnData = msg(1, 200, 'Conectado OK! ', $db_connection);
        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage());
        }

    //endif;

}

echo json_encode($returnData);
