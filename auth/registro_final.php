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

// Admitir solo POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0, 404, 'La página solicitada no existe!');

// Comprobar que se han recibido los datos necesarios
elseif(!isset($datos['nombre']) 
    || !isset($datos['nombre_completo'])
    || !isset($datos['telefono_movil'])
    || !isset($datos['email'])
    || !isset($datos['lopd'])
    || !isset($datos['acepta_correos'])
    || !isset($datos['contrasenia'])
    || empty(trim($datos['nombre']))
    || empty(trim($datos['nombre_completo']))
    || empty(trim($datos['telefono_movil']))
    || empty(trim($datos['email']))
    || empty(trim($datos['contrasenia']))
    ):

    $fields = ['fields' => ['nombre', 'nombre_completo', 'telefono_movil', 'email', 'lopd', 'acepta_correos', 'contrasenia']];
    $returnData = msg(0,422,'Faltan datos necesarios: ', $fields);

// Si se han recibido los campos necesarios
else:
    
    try{

        // Cargar las variables con los valores recibidos
        $nombre = $datos['nombre'];
        $nombre_completo = $datos['nombre_completo'];
        $telefono_movil = $datos['telefono_movil'];
        $email = $datos['email'];
        $lopd = $datos['lopd'];
        $acepta_correos = $datos['acepta_correos'];
        $password = $datos['contrasenia'];
    
        // Validaciones
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
            $returnData = msg(0, 422, 'El dato indicado en el correo electrónico no tiene el formato correcto. ');
        
        elseif(strlen($password) < 6):
            $returnData = msg(0, 422, 'La contraseña debe tener al menos 6 letras o números. ');

        elseif(strlen($nombre) < 3):
            $returnData = msg(0, 422, 'El nombre debe tener al menos 3 letras o números. ');

        else:
            
            // Comprobar si el usuario está pendiente de registro
            $check_email = "SELECT `clave`, `email`, `rest_contrasenia`, `nrotemporal` FROM `usuarios` WHERE `email` = :email AND `deleted` = 0";
            $check_email_stmt = $conn->prepare($check_email);
            $check_email_stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $check_email_stmt->execute();

            if($check_email_stmt->rowCount()):

                $results = array();
                $results = $check_email_stmt->fetch(PDO::FETCH_ASSOC);
                $clave_usr = $results['clave'];
                $nroRegistro = $results['nrotemporal'];

                if($results['rest_contrasenia'] == 0):
                    
                    $returnData = msg(0, 422, 'Este correo electrónico ya se encuentra registrado!');
                
                else:
                
                    // --------------------------------------------------------------------------------------------- //
                    // Cadena para actualizar el registro de usuarios
                    $update_query_usr = "UPDATE `usuarios` SET ";
                    $update_query_usr .= "`nombre` = :nombre, ";
                    $update_query_usr .= "`nombre_completo` = :nombre_completo, ";
                    $update_query_usr .= "`telefono_movil` = :telefono_movil, ";
                    $update_query_usr .= "`rest_contrasenia` = 0, `nrotemporal` = '', ";
                    $update_query_usr .= "`lopd` = :lopd, ";
                    $update_query_usr .= "`acepta_emails` = :acepta_correos, ";
                    $update_query_usr .= "`contrasenia` = :password ";
                    $update_query_usr .= "WHERE `email` = :email AND `deleted` = 0";

                    // Actualizar USUARIOS
                    $update_usr_stmt = $conn->prepare($update_query_usr);

                    // Rellenar datos de la instrucción para usuarios
                    $update_usr_stmt->bindValue(':nombre', htmlspecialchars(strip_tags($nombre)),PDO::PARAM_STR);
                    $update_usr_stmt->bindValue(':nombre_completo', htmlspecialchars(strip_tags($nombre_completo)),PDO::PARAM_STR);
                    $update_usr_stmt->bindValue(':telefono_movil', htmlspecialchars(strip_tags($telefono_movil)),PDO::PARAM_STR);
                    $update_usr_stmt->bindValue(':lopd', $lopd,PDO::PARAM_INT);
                    $update_usr_stmt->bindValue(':acepta_correos', $acepta_correos,PDO::PARAM_INT);
                    $update_usr_stmt->bindValue(':email', $email,PDO::PARAM_STR);
                    $update_usr_stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT),PDO::PARAM_STR);
                    
                    // Ejecutar
                    $update_usr_stmt->execute();

                    // --------------------------------------------------------------------------------------------- //
                    // SOLO USUARIOS CLIENTES
                    // --------------------------------------------------------------------------------------------- //
                    if($nroRegistro >= 100000 && $nroRegistro <=900000):
                        // ----------------------------------------------------------------------------------------------- //
                        // Cadena para actualizar clientes
                        $update_query_cli = "UPDATE `clientes` SET `registrado_hd` = 1 WHERE `email` = :email";

                        // Actualizar CLIENTES
                        $update_cli_stmt = $conn->prepare($update_query_cli);

                        // Rellenar datos de la instrucción para clientes
                        $update_cli_stmt->bindValue(':email', $email, PDO::PARAM_STR);

                        // Ejecutar
                        $update_cli_stmt->execute();

                        // --------------------------------------------------------------------------------------------- //
                        // Cadena para asignar el grupo
                        $update_query_grp = "INSERT INTO `rel_usr_grupos` (`padre`, `clave_usuario`, `clave_grupo`) VALUES (:clave_grupo, :clave_usuario, 0)";

                        // Actualizar REL_USR_GRUPOS
                        $update_grp_stmt = $conn->prepare($update_query_grp);

                        $update_grp_stmt->bindValue(':clave_usuario', $clave_usr, PDO::PARAM_INT);
                        $update_grp_stmt->bindValue(':clave_grupo', 1, PDO::PARAM_INT);

                        // Ejecutar
                        $update_grp_stmt->execute();
                        
                    endif;
                    // --------------------------------------------------------------------------------------------- //
                    // TODOS
                    // --------------------------------------------------------------------------------------------- //
                    // Cadena para insertar en MIGAS_DE_PAN
                    $update_query_mdp = "INSERT INTO `migas_de_pan` (`tipo`, `fecha_y_hora`, `clave_usuario`, `texto_extra`) VALUES ('REG', NOW(), :clave_usuario, :texto_extra)";

                    // Actualizar MIGAS_DE_PAN
                    $update_mdp_stmt = $conn->prepare($update_query_mdp);

                    $update_mdp_stmt->bindValue(':clave_usuario', $clave_usr, PDO::PARAM_INT);
                    $update_mdp_stmt->bindValue(':texto_extra', 'Registro inicial del usuario', PDO::PARAM_STR);

                    // Ejecutar
                    $update_mdp_stmt->execute();
                    // --------------------------------------------------------------------------------------------- //

                    // FIN OK
                    $returnData = msg(1, 201, 'Usuario registrado con exito');

                endif;
            else:
                $returnData = msg(0, 422, 'Este correo electrónico no tiene el registro pendiente!');
            endif;

        endif;
    }
    catch(PDOException $e){
        $returnData = msg(0, 500, $e->getMessage());
    }
endif;

echo json_encode($returnData);
