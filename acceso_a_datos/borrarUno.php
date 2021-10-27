<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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


function msg($success, $status, $message, $extra = []){
    return [
        'success' => $success,
        'status' => $status,
        'message' => $message,
        'data' => $extra];
}

// Comprobar token de login
if($auth->isAuth()){

    // Coger datos recibidos
    $datos = json_decode(file_get_contents("php://input"), true);
    $returnData = [];

    // Admitir solo POST
    if($_SERVER["REQUEST_METHOD"] != "POST"):
        $returnData = msg(0, 404, 'La página solicitada no existe!');


    // Comprobar que se han recibido los datos necesarios
    elseif(!isset($datos['tabla']) 
    || !isset($datos['clave'])
    || empty($datos['tabla'])
    || empty($datos['clave'])
    ):

    $fields = ['fields' => ['tabla','clave']];
    $returnData = msg(0, 422, 'Faltan datos necesarios!', $fields); 

    // Si se han recibido los datos necesarios
    else:

        $tabla = $datos['tabla'];
        $clave = $datos['clave'];

        try{

            $update_query = "UPDATE " . $tabla . " SET `deleted` = 1, `deleted_at` = NOW() WHERE `clave` = :clave";
            $update_stmt = $conn->prepare($update_query);

            // DATA BINDING
            $update_stmt->bindValue(':clave', $clave,PDO::PARAM_STR);
            $update_stmt->execute();

            $returnData = msg(1, 200, 'Registro eliminado con exito');

        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage());
        }

    endif;

}

echo json_encode($returnData);
