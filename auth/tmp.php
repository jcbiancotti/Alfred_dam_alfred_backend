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

    try{


        // --------------------------------------------------------------------------------------------- //
        // Cadena para insertar en MIGAS_DE_PAN
        $update_query_mdp = "INSERT INTO `migas_de_pan` (`tipo`, `fecha_y_hora`, `clave_usuario`, `texto_extra`) VALUES ('REG', NOW(), :clave_usuario, :texto_extra)";

        // Actualizar MIGAS_DE_PAN
        $update_mdp_stmt = $conn->prepare($update_query_mdp);

        //$update_mdp_stmt->bindValue(':fecha_y_hora', NOW(), PDO::PARAM_STR);
        $update_mdp_stmt->bindValue(':clave_usuario', 100, PDO::PARAM_INT);
        $update_mdp_stmt->bindValue(':texto_extra', 'Registro inicial del usuario', PDO::PARAM_STR);

        // Ejecutar
        $update_mdp_stmt->execute();
        // --------------------------------------------------------------------------------------------- //

        // FIN OK
        $returnData = msg(1, 201, 'Usuario registrado con exito');

    }
    catch(PDOException $e){
        $returnData = msg(0, 500, $e->getMessage() . $update_query);
    }

echo json_encode($returnData);
