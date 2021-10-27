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
        || empty(trim($datos['tabla']))
        || empty(trim($datos['clave']))
        ):

        $fields = ['fields' => ['tabla', 'clave']];
        $returnData = msg(0, 422, 'Faltan datos necesarios!', $fields); 

    // Si se han recibido los datos necesarios
    else:

        $tabla = trim($datos['tabla']);
        $clave = trim($datos['clave']);

        try{

            $consulta = "SELECT * FROM `$tabla` WHERE `clave` = :clave AND `deleted` = 0";
            $consulta_stmt = $conn->prepare($consulta);
            $consulta_stmt->bindValue(':clave', $clave, PDO::PARAM_STR);
            $consulta_stmt->execute();

            if($consulta_stmt->rowCount()):
                
                $results = array();
                $data = array();
                $i = 0;
                while($results = $consulta_stmt->fetch(PDO::FETCH_ASSOC)){
                    $data[$i] = $results;
                    $i++;
                }
                // Remover el campo contrasenia
                unset($data['contrasenia']); 

                $returnData = msg(1, 201, 'Datos obtenidos ok! ', $data);

            else:
                $returnData = msg(1, 422, 'No hay datos para la consulta requerida!');
            endif;
        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage());
        }

    endif;

}

echo json_encode($returnData);
