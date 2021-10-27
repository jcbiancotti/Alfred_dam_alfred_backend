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
    || !isset($datos['campos'])
    || !isset($datos['objeto']) 
    || empty($datos['tabla'])
    || empty($datos['clave'])
    || empty($datos['campos'])
    || empty($datos['objeto'])
    ):

    $fields = ['fields' => ['tabla','clave','campos','objeto']];
    $returnData = msg(0, 422, 'Faltan datos necesarios!', $fields); 

    // Si se han recibido los datos necesarios
    else:

        $tabla = $datos['tabla'];
        $clave = $datos['clave'];
        $campos = $datos['campos'];
        $objeto = $datos['objeto'];

        try{

            $update_query = "UPDATE " . $tabla . " SET updated_at = NOW(),";
            $final_text = " WHERE `clave` = :clave";
            
            foreach($campos as $campo) {
                $update_query = $update_query . $campo . " = :" . $campo . ",";
            };
            $update_query = substr($update_query, 0, -1) . $final_text;

            $update_stmt = $conn->prepare($update_query);

            // DATA BINDING
            $update_stmt->bindValue(':clave', $clave,PDO::PARAM_STR);
            foreach($campos as $campo) {

                if(isset($objeto[$campo])) {
                    if(strtolower($campo) == 'contrasenia' || strtolower($campo) == 'password'){
                        $update_stmt->bindValue(":" . $campo, password_hash(trim($objeto[$campo]), PASSWORD_DEFAULT), PDO::PARAM_STR);
                    } else {
                        $update_stmt->bindValue(":" . $campo, htmlspecialchars(strip_tags(trim($objeto[$campo]))), PDO::PARAM_STR);
                    };
                } else {
                    $update_stmt->bindValue(":" . $campo, "", PDO::PARAM_STR);
                }

            }

            $update_stmt->execute();

            $returnData = msg(1, 200, 'Datos actualizados con exito');

        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage());
        }

    endif;

}

echo json_encode($returnData);
