<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


require __DIR__.'/../classes/Database.php';
require __DIR__.'/../classes/JwtHandler.php';

$allHeaders = getallheaders();
$db_connection = new Database();
$conn = $db_connection->dbConnection();

function msg($success, $status, $message, $extra = []){
    return [
        'success' => $success,
        'status' => $status,
        'message' => $message,
        'data' => $extra];
}
$returnData = [
    "success" => 0,
    "status" => 401,
    "message" => "No autorizado"
];

// Coger datos recibidos
$datos = json_decode(file_get_contents("php://input"), true);

// Admitir solo POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0, 404, 'La página solicitada no existe!');

// Comprobar que se han recibido los datos necesarios
elseif(!isset($datos['email']) 
    || !isset($datos['password'])
    || empty(trim($datos['email']))
    || empty(trim($datos['password']))
    ):

    $fields = ['fields' => ['email','password']];
    $returnData = msg(0,422,'Faltan datos necesarios: ',$fields);

// Si se han recibido los campos necesarios
else:
    $email = trim($datos['email']);
    $password = trim($datos['password']);

    // Comprobar el formato del correo electrónico
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
        $returnData = msg(0, 422, 'El dato indicado como correo electrónico no tiene el formato correcto!');
    
    // Comprobar longitud de la password
    elseif(strlen($password) < 6):
        $returnData = msg(0, 422, 'Tu contraseña debe tener una longitud mínima de 6 letras o números!');

    // Comprobar el login
    else:
        try{
            
            $query_login = "SELECT * FROM `usuarios` WHERE `email` = :email AND `deleted` = 0";
            $query_login_stmt = $conn->prepare($query_login);
            $query_login_stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $query_login_stmt->execute();

            // Si el correo electrónico existe
            if($query_login_stmt->rowCount()):
                
                $row = $query_login_stmt->fetch(PDO::FETCH_ASSOC);
                $rest_password = $row['rest_contrasenia'];
                $nrotemporal = intval($row['nrotemporal']);

                // Si está pendiente de restablecer la contraseña
                if($rest_password == 1):
                    if($nrotemporal < 100000):
                        $returnData = msg(0, 422, 'En proceso de cambiar la contraseña. Comprueba tu correo.');
                        //$returnData = msg(1, 207, 'Cambio de contraseña!');
                    else:
                        $returnData = msg(0, 422, 'En proceso de registro. Comprueba tu correo.');
                        //$returnData = msg(1, 206, 'Completar perfil y crear contraseña!');
                    endif;

                else:
                    // Comprobar la password
                    // Si la password es correcta devolver un tocken
                    $check_password = password_verify($password, $row['contrasenia']);

                    if($check_password):

                        $jwt = new JwtHandler();
                        $token = $jwt->_jwt_encode_data(
                            'dam_alfred',
                            array("user_id"=> $row['clave'])
                        );
                        
                        $returnData = msg(1, 201, 'Usuario identificado correctamente.', $token);

                    // Si la password es incorrecta
                    else:
                        $returnData = msg(0, 422, 'Contraseña incorrecta!');
                    endif;
                    
                endif;

            // Si el correo electrónico no existe
            else:
                $returnData = msg(0, 422, 'Dirección de correo electrónico no registrada!');
            endif;
        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage());
        }

    endif;

endif;

echo json_encode($returnData);
