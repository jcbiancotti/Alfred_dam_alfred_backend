<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__.'/../classes/Database.php';

$allHeaders = getallheaders();
$db_connection = new Database();
$conn = $db_connection->dbConnection();

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

// Coger datos recibidos
$datos = json_decode(file_get_contents("php://input"), true);
$returnData = [];

// Admitir solo POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0, 404, 'La página solicitada no existe!');

// Comprobar que se han recibido los datos necesarios
elseif(!isset($datos['correo']) 
    || empty(trim($datos['correo']))
    ):

    $fields = ['fields' => ['correo']];
    $returnData = msg(0, 422, 'Faltan datos necesarios!', $fields); 

// Si se han recibido los datos necesarios
else:

    $correo = trim($datos['correo']);

    try{

        $consulta = "SELECT * FROM `clientes` WHERE `email` = :correo";
        $consulta_stmt = $conn->prepare($consulta);
        $consulta_stmt->bindValue(':correo', $correo, PDO::PARAM_STR);
        $consulta_stmt->execute();

        if($consulta_stmt->rowCount()): 
            $returnData = msg(1, 201, 'Este correo es de un cliente!');

        else:
            $returnData = msg(1, 422, 'Este correo no corresponde a un cliente de la empresa!');

        endif;
    }
    catch(PDOException $e){
        $returnData = msg(0, 500, $e->getMessage());
    }

endif;

echo json_encode($returnData);

