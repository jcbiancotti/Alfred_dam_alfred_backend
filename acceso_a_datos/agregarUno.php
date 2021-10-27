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
        || !isset($datos['campos'])
        || !isset($datos['objeto']) 
        || empty($datos['tabla'])
        || empty($datos['campos'])
        || empty($datos['objeto'])
        ):
    
        $fields = ['fields' => ['tabla', 'campos','objeto']];
        $returnData = msg(0, 422, 'Faltan datos necesarios!', $fields); 

    // Si se han recibido los datos necesarios
    else:

        $tabla = $datos['tabla'];
        $campos = $datos['campos'];
        $objeto = $datos['objeto'];

        try{

            $insert_query = "INSERT INTO `$tabla` (";
            $final_text = ") VALUES (";
            
            foreach($campos as $campo) {
                $insert_query = $insert_query . $campo . ",";
                $final_text = $final_text . ":" . $campo . ",";
            };
            $insert_query = substr($insert_query, 0, -1) . substr($final_text, 0, -1) . ")";

            $insert_stmt = $conn->prepare($insert_query);

            // Completar la sentencia para ejecutar
            foreach($campos as $campo) {

                if(isset($objeto[$campo])) {
                    if(strtolower($campo) == 'contrasenia' || strtolower($campo) == 'password'){
                        $insert_stmt->bindValue(":" . $campo, password_hash(trim($objeto[$campo]), PASSWORD_DEFAULT), PDO::PARAM_STR);
                    } else {
                        $insert_stmt->bindValue(":" . $campo, htmlspecialchars(strip_tags(trim($objeto[$campo]))), PDO::PARAM_STR);
                    };
                } else {
                    $insert_stmt->bindValue(":" . $campo, "", PDO::PARAM_STR);
                }
                
            }

            $insert_stmt->execute();
            $idInsertado = $conn->lastInsertId();
            $id = array();
            $id[0] = ['nuevoId' => $idInsertado];


            $returnData = msg(1, 201, 'Datos almacenados con exito!', $id);
           
        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage());
        }

    endif;

}

echo json_encode($returnData);
