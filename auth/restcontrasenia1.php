<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__.'/../classes/Database.php';

// Utilidades para enviar correo electronico
require __DIR__.'/../utilidades/enviarMail.php';
$alf_correos = new AlfredMails();

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

        $consulta = "SELECT * FROM `usuarios` WHERE `email` = :correo";
        $consulta_stmt = $conn->prepare($consulta);
        $consulta_stmt->bindValue(':correo', $correo, PDO::PARAM_STR);
        $consulta_stmt->execute();

        if($consulta_stmt->rowCount()): 

            
            $results = array();
            $results = $consulta_stmt->fetch(PDO::FETCH_ASSOC);

            // Este correo electrónico ya tiene el registro pendiente
            $nombre = $results['nombre_completo'];
            $oldpass = $results['contrasenia'];

            // Crear el número aleatorio para el registro
            // 10000 a 99999 --> Cambio de contraseña
            // 100000 a 900000 --> Registro cliente
            // 900001 a 999999 --> Registro Usuarios
            $nroRegistro = strval(rand(10000, 99999));

            // Agregar el usuario en la tabla
            $update_query = "UPDATE `usuarios` SET `rest_contrasenia` = 1, `nrotemporal` = :nrotemporal WHERE `email` = :correo";
            $update_stmt = $conn->prepare($update_query);

            // Reempazar los valores del stmt
            $update_stmt->bindValue(':correo', $correo, PDO::PARAM_STR);
            $update_stmt->bindValue(':nrotemporal', $nroRegistro, PDO::PARAM_STR);

            // Ejecutar
            $update_stmt->execute();

            // Enviar correo electrónico a la dirección indicada
            $texto =  '<img src="https://biancotti.es/dam_alfred_backend/assets/img/alfred-logo-header.png" width="150" height="60" alt="Alfred-app!">';
            $texto .= '<p><h1>' . $nombre . ',</h1></p><p>Te hemos enviado este correo porque has solicitado restablecer la contraseña.</p>';
            $texto .= '<p>Pincha en el siguiente enlace y podrás establecer una nueva.</p>';
            $texto .= '<p><a href="http://localhost:8081/restcontrasenia2/' . $correo . '/' . $nroRegistro . '/' . $oldpass . '">Pincha aquí</a></p>';
            $texto .= '<BR>';

            $alf_correos->emEnviar($correo, 'Completa el cambio de contraseña!', $texto);

            $returnData = msg(1, 201, 'Comprueba tu correo para completar el cambio!');

        else:
            $returnData = msg(1, 422, 'Este correo no corresponde a un usuario registrado!');

        endif;
    }
    catch(PDOException $e){
        $returnData = msg(0, 500, $e->getMessage() . $update_query);
    }

endif;

echo json_encode($returnData);

