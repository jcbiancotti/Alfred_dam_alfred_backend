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

set_time_limit(0);  // 0 = sin limite de tiempo

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

    // GET DATA FORM REQUEST
    $datos = json_decode(file_get_contents("php://input"), true);
    $returnData = [];
    
    // IF REQUEST METHOD IS NOT POST
    if($_SERVER["REQUEST_METHOD"] != "POST"):
        $returnData = msg(0, 404, 'La página solicitada no existe!');

    else:

        
        try{
            
 
            //*******
            $insert_query = "INSERT INTO `callejero` (`clave`, `cod_prov`, `nom_prov`, `cod_postal`, `cod_ine`, `nom_muni`, `tipo_via`, `nom_via`) VALUES
            (1, 40, 'SEGOVIA', '40223', '40223', 'ANGELES VÜGAS MATUTE', 'CALLE', 'GUÑJUELO'),
            (2, 43, 'TARRAGONA', '43783', '43110', 'POBLÇA DE MASSALUCA (LA)', '.', 'POL. 1 PARC. 153'),
            (3, 5, 'AVILA', '05480', '05047', 'CANDELEDA', 'PRAJE', 'COÁÑADO LA UMBRIA')";

            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->execute();            

            //*******
           
            $returnData = msg(0, 200, 'Filas importadas OK');
           
        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage());
        }

    endif;

}

echo json_encode($returnData);

