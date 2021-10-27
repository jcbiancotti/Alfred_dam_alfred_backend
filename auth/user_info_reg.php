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
    || !isset($datos['codigo'])
    || empty(trim($datos['correo']))
    || empty(trim($datos['codigo']))
    ):

    $fields = ['fields' => ['correo', 'codigo']];
    $returnData = msg(0, 422, 'Faltan datos necesarios!', $fields); 

// Si se han recibido los datos necesarios
else:

    $correo = trim($datos['correo']);
    $codigo = trim($datos['codigo']);

    try{

        // Comprobar si es CAMBIO DE CONTRASEÑA, CLIENTE o USUARIO
        // 10000 a 99999 --> Cambio de contraseña
        // 100000 a 900000 --> Registro cliente
        // 900001 a 999999 --> Registro Usuarios
        $consulta0 = "SELECT `nrotemporal` FROM `usuarios` WHERE `email` = :correo AND deleted = 0";
        $consulta0_stmt = $conn->prepare($consulta0);
        $consulta0_stmt->bindValue(':correo', $correo, PDO::PARAM_STR);
        $consulta0_stmt->execute();

        if($consulta0_stmt->rowCount()):

            $results = array();
            $results = $consulta0_stmt->fetch(PDO::FETCH_ASSOC);
            
            if($results['nrotemporal'] >= 900001 || $results['nrotemporal'] < 100000):
                // Consulta USUARIO o CAMBIO DE CONTRASEÑA
                $consulta = "SELECT clave, nombre, nombre_completo, email, telefono_movil, lopd, acepta_emails ";
                $consulta .= "FROM `usuarios` ";
                $consulta .= "WHERE email = :correo AND ";
                if($codigo != '-1'):
                    $consulta .= "nrotemporal = :codigo AND ";
                endif;
                $consulta .= "rest_contrasenia = 1 AND deleted = 0";

            else:
                // Consulta cuando es CLIENTE
                $consulta = "SELECT u.clave, u.nombre, c.nombre as nombre_completo, u.email, c.telefono_movil, u.lopd, u.acepta_emails ";
                $consulta .= "FROM `usuarios` u LEFT OUTER JOIN `clientes` c ON u.email = c.email ";
                $consulta .= "WHERE u.email = :correo AND u.nrotemporal = :codigo AND ";
                $consulta .= "u.rest_contrasenia = 1 AND u.deleted = 0";

            endif;
            $consulta_stmt = $conn->prepare($consulta);
            $consulta_stmt->bindValue(':correo', $correo, PDO::PARAM_STR);
            if($codigo != '-1'):
                $consulta_stmt->bindValue(':codigo', $codigo, PDO::PARAM_STR);
            endif;
            $consulta_stmt->execute();

            if($consulta_stmt->rowCount()): 

                $results = array();
                $results = $consulta_stmt->fetch(PDO::FETCH_ASSOC);
                $returnData = msg(1, 201, 'Datos recuperados ok!', $results);
    
            else:

                $returnData = msg(1, 422, 'Este correo no existe!');

            endif;

        else:

            $returnData = msg(1, 422, 'Este correo no existe!');
            
        endif;

    }
    catch(PDOException $e){
        $returnData = msg(0, 500, $e->getMessage());
    }

endif;

echo json_encode($returnData);
