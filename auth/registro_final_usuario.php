<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

// Conexion a la BBDD
require __DIR__.'/../classes/Database.php';
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// Utilidades para enviar correo electronico
require __DIR__.'/../utilidades/enviarMail.php';
$alf_correos = new AlfredMails();

// Datos recibidos en la invocación
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// Solo se aceptará el método POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0, 404, 'La página solicitada no existe!');

// Comprobar si se han recibido los datos necesarios
elseif(!isset($data->nombre)
    || !isset($data->email) 
    || empty(trim($data->nombre))
    || empty(trim($data->email))
    ):


    $fields = ['fields' => ['nombre', 'email']];
    $returnData = msg(0,422,'Faltan datos necesarios: ', $fields);

// Si se han recibido los campos necesarios
else:
    
    $nombre = trim($data->nombre);
    $email = trim($data->email);

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
        $returnData = msg(0,422,'El dato indicado en el correo electrónico no tiene el formato correcto!');

    // Largo mínimo del nombre 3 caracteres
    elseif(strlen($nombre) < 3):
        $returnData = msg(0, 422, 'El nombre de usuario debe tener al menos 3 letras o números!');

    else:

        try{

            // Comprobar si el usuario está pendiente de registro
            $check_email = "SELECT `clave`, `email`, `rest_contrasenia` FROM `usuarios` WHERE `email` = :email AND `deleted` = 0 AND `rest_contrasenia` = 0";
            $check_email_stmt = $conn->prepare($check_email);
            $check_email_stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $check_email_stmt->execute();

            if($check_email_stmt->rowCount()):

                $returnData = msg(1, 208, 'Este correo electrónico ya se encuentra registrado!');
                
            else:

                // Crear el número aleatorio para el registro
                // 10000 a 99999 --> Cambio de contraseña
                // 100000 a 900000 --> Registro cliente
                // 900001 a 999999 --> Registro Usuarios
                $nroRegistro = strval(rand(900001, 999999));

                // Actualizar el numero aleatorio en el usuario
                $update_query = "UPDATE `usuarios` SET `nrotemporal` = :nrotemporal WHERE `email` = :email AND `deleted` = 0";
                $update_stmt = $conn->prepare($update_query);

                // Reempazar los valores del stmt
                $update_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $update_stmt->bindValue(':nrotemporal', $nroRegistro, PDO::PARAM_STR);

                // Ejecutar
                $update_stmt->execute();

                // Enviar correo electrónico a la dirección indicada
                $texto =  '<img src="https://biancotti.es/dam_alfred_backend/assets/img/alfred-logo-header.png" width="150" height="60" alt="Alfred-app!">';
                $texto .= '<p><h1>' . $nombre . ',</h1></p><p>Te damos la bienvenida. Estas a un click de completar el registro en la aplicación.</p>';
                $texto .= '<p>Pincha en el siguiente enlace y habremos terminado.</p>';
                $texto .= '<p><a href="https://biancotti.es/#/registro_fin/' . $email . '/' . $nroRegistro . '">Pincha aquí</a></p>';
                $texto .= '<BR>';
                $texto .= '<p>Recibes este correo porque has solicitado registrarte en Alfred.es. Si no has sido tú, ignóralo.</p>';

                $alf_correos->emEnviar($email, 'Completa tu registro en Alfred!', $texto);

                $returnData = msg(1, 201, 'Comprueba tu correo para completar el registro!');

            endif;

        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }
    endif;
    
endif;

echo json_encode($returnData);
