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

    // Comprobar que se han recibido los datos necesarios
    elseif(!isset($datos['correo']) 
        || !isset($datos['oldpass'])
        || !isset($datos['newpass'])
        || empty(trim($datos['correo']))
        || empty(trim($datos['oldpass']))
        || empty(trim($datos['newpass']))
        ):

        $fields = ['fields' => ['correo', 'oldpass', 'newpass']];
        $returnData = msg(0, 422, $datos, $fields);

    // Si se han recibido los campos necesarios
    else:
        
        try{

            // Cargar las variables con los valores recibidos
            $correo = $datos['correo'];
            $oldpass = $datos['oldpass'];
            $newpass = $datos['newpass'];
        
            // Validaciones
            if(strlen($newpass) < 6):
                $returnData = msg(0, 422, 'La contraseña debe tener al menos 6 letras o números. ');

            else:
            
                // Comprobar si el usuario está registrado
                $check_email = "SELECT `clave`, `contrasenia` FROM `usuarios` WHERE `email` = :email AND `deleted` = 0";
                $check_email_stmt = $conn->prepare($check_email);
                $check_email_stmt->bindValue(':email', $correo, PDO::PARAM_STR);
                $check_email_stmt->execute();

                if($check_email_stmt->rowCount()):

                    $results = array();
                    $results = $check_email_stmt->fetch(PDO::FETCH_ASSOC);
                    $clave_usr = $results['clave'];

                    // Comprobar la password antigua
                    $check_password = password_verify($oldpass, $results['contrasenia']);

                    if(!$check_password):
                        $returnData = msg(0, 422, 'La contraseña anterior proporcionada no es correcta!');

                    else:
                        // Actualizar la contraseña
                        // --------------------------------------------------------------------------------------------- //
                        // Cadena para actualizar el registro de usuarios
                        $update_query_usr = "UPDATE `usuarios` SET ";
                        $update_query_usr .= "`contrasenia` = :password ";
                        $update_query_usr .= "WHERE `email` = :email AND `deleted` = 0";

                        // Actualizar USUARIOS
                        $update_usr_stmt = $conn->prepare($update_query_usr);

                        // Rellenar datos de la instrucción para usuarios
                        $update_usr_stmt->bindValue(':email', $correo,PDO::PARAM_STR);
                        $update_usr_stmt->bindValue(':password', password_hash($newpass, PASSWORD_DEFAULT),PDO::PARAM_STR);
                    
                        // Ejecutar
                        $update_usr_stmt->execute();

                        // --------------------------------------------------------------------------------------------- //
                        // Cadena para insertar en MIGAS_DE_PAN
                        $update_query_mdp = "INSERT INTO `migas_de_pan` (`tipo`, `fecha_y_hora`, `clave_usuario`, `texto_extra`) VALUES ('UPD', NOW(), :clave_usuario, :texto_extra)";

                        // Actualizar MIGAS_DE_PAN
                        $update_mdp_stmt = $conn->prepare($update_query_mdp);

                        $update_mdp_stmt->bindValue(':clave_usuario', $clave_usr, PDO::PARAM_INT);
                        $update_mdp_stmt->bindValue(':texto_extra', 'Cambio de contraseña desde la aplicación', PDO::PARAM_STR);

                        // Ejecutar
                        $update_mdp_stmt->execute();
                        // --------------------------------------------------------------------------------------------- //

                        // FIN OK
                        $returnData = msg(1, 200, 'Contraseña actualizada!');

                    endif;

                else:
                    $returnData = msg(0, 422, 'Este correo electrónico no está registrado!');
                endif;

            endif;

        } catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage() . $update_query);
        }

    endif;

}

echo json_encode($returnData);
