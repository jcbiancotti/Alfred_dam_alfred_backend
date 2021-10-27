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
    elseif(!isset($datos['clave'])
        || empty(trim($datos['clave']))
        ):

        $fields = ['fields' => ['clave','filtros[] (opcional)']];
        $returnData = msg(0, 422, 'Faltan datos necesarios!', $fields); 

    // Si se han recibido los datos necesarios
    else:

        $Qclave = trim($datos['clave']);
        $req_query = "SELECT * FROM `sys_querys` WHERE clave = :clave and deleted = 0" ;

        if(isset($datos['filtros'])) {
            $filtros = $datos['filtros'];
        } else {
            $filtros = [];
        }

        try{

            $query_stmt = $conn->prepare($req_query);
            $query_stmt->bindValue(':clave', $Qclave, PDO::PARAM_STR);
            $query_stmt->execute();

            if($query_stmt->rowCount()):

                $results = array();
                $results = $query_stmt->fetch(PDO::FETCH_ASSOC);

                // Consulta encontrada: Ejecutar consulta
                $query_consulta = $results['consulta'];
                $consulta_stmt = $conn->prepare($query_consulta);
                
                // Completar los filtros
                if(count($filtros) > 0) {
                    foreach($filtros as $filtro) {
                        $consulta_stmt->bindValue(':' . $filtro['field'], $filtro['valor'], PDO::PARAM_STR);
                    }
                }
                $consulta_stmt->execute();

                if($consulta_stmt->rowCount()):

                    $consulta_results = array();
                    $data = array();

                    $i = 0;
                    while($consulta_results = $consulta_stmt->fetch(PDO::FETCH_ASSOC)){
                        $data[$i] = $consulta_results;
                        $i++;
                    }
                    $returnData = msg(1, 201, 'Datos recuperados con exito!', $data);

                else:
                    $returnData = msg(1, 422, 'No hay datos para la consulta solicitada!');
                endif;

            else:
                $returnData = msg(0, 422, 'Se está solicitando una consulta que no existe, comprueba que la clave ' . $Qclave . ' exite en la tabla SYS_QUERYS!');
            endif;

        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $query_consulta . '////' . $e->getMessage());
        }

    endif;

}

echo json_encode($returnData);
