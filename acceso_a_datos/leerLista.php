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
        || empty(trim($datos['tabla']))
        || empty($datos['campos'])
        ):
        // where no es obligatorio

        $fields = ['fields' => ['tabla', 'campos', 'where (opcional)','order (opcional)']];
        $returnData = msg(0, 422, 'Faltan datos necesarios!', $fields); 

    // Si se han recibido los datos necesarios
    else:

        $tabla = trim($datos['tabla']);
        $campos = $datos['campos'];
        $where = trim($datos['where']);
        $order = trim($datos['order']);

        $consulta = "SELECT ";
        $xcampos = "";
        foreach($campos as $campo) {
            $xcampos = $xcampos . $campo . ",";
        };

        try{

            $consulta .= substr($xcampos, 0, -1) . " FROM " . $tabla;
            $consulta .= " WHERE `deleted` = 0 ";
            if(trim($where) != "") {
                $consulta = $consulta . " AND " . $where;
            };
            if(trim($order) != "") {
                $consulta = $consulta . " ORDER BY " . $order;
            };
            $consulta_stmt = $conn->prepare($consulta);
            $consulta_stmt->execute();

            if($consulta_stmt->rowCount()):
                
                $results = array();
                $data = array();
                $i = 0;
                while($results = $consulta_stmt->fetch(PDO::FETCH_ASSOC)){
                    $data[$i] = $results;
                    $i++;
                }
                $returnData = msg(1, 201, 'Datos recuperados con exito!', $data);

            else:
                $returnData = msg(1, 422, 'No hay datos para la consulta requerida! =>' . $consulta);
            endif;
        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage() . $consulta);
        }

    endif;

}

echo json_encode($returnData);

