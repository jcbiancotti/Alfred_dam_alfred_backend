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

    // Admitir solo POST
    if($_SERVER["REQUEST_METHOD"] != "POST"):
        $returnData = msg(0, 404, 'La página solicitada no existe!');


    // Si se han recibido los datos necesarios
    else:

        try{

            $recibo = $_FILES['file']['name'];
            $ruta = "./../adjuntos/";
            if(substr($recibo, 0,3) == 'TKT') {
                $ruta .= 'tickets/' . substr($recibo, 3, 6) . '/';
            }
            $file_name = $ruta . substr($recibo,3); 

            // Si no hay una carpeta para el ticket la crea
            if (!file_exists($ruta)) {
                mkdir($ruta, 0777, true);
            }


            if(move_uploaded_file($_FILES['file']['tmp_name'], $file_name))
            {
                $returnData = msg(1, 200, 'Fichero almacenado con exito ->' . $file_name);
            }
            else
            {
                $returnData = msg(0, 422, 'Error al intentar almacenar el Fichero ->' . $file_name);
            }

        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage());
        }

    endif;

}

echo json_encode($returnData);

