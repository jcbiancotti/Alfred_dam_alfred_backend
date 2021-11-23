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

// Utilidades para enviar correo electronico
require __DIR__.'/../utilidades/enviarMail.php';
$alf_correos = new AlfredMails();

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
    elseif(!isset($datos['destino'])
        || !isset($datos['asunto'])
        || !isset($datos['mensaje'])
        || empty(trim($datos['destino']))
        || empty(trim($datos['asunto']))
        || empty(trim($datos['mensaje']))
        ):

        $fields = ['fields' => ['destino', 'asunto','mensaje']];
        $returnData = msg(0, 422, 'Faltan datos necesarios!', $fields); 

    // Si se han recibido los datos necesarios
    else:
        //////////////// VALORES RECIBIDOS ///////////////////////////////////////////////
        $destino = trim($datos['destino']);
        $asunto = trim($datos['asunto']);
        $mensaje = trim($datos['mensaje']);
        //////////////////////////////////////////////////////////////////////////////////
        try{

            // Enviar correo electrónico a la dirección indicada
            $texto =  '<img src="https://biancotti.es/dam_alfred_backend/assets/img/alfred-logo-header.png" width="150" height="60" alt="Alfred-app!">';
            $texto .= '<p>' . $mensaje . '</p>';
            $texto .= '<BR>';
            $texto .= '<p>Recibes este correo porque has autorizado las comunicaciones por email desde Alfred, la aplicación de atención al cliente de LA EMPRESA S.L.</p>';
            $texto .= '<p>Si deseas dejar de recibir estas comunicaciones indícalo en tu perfil de la aplicación.</p>';

            $alf_correos->emEnviar($destino, $asunto, $texto);

            $returnData = msg(1, 201, 'Correo enviado!');
                    
        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage() );
        }

    endif;

}

echo json_encode($returnData);


