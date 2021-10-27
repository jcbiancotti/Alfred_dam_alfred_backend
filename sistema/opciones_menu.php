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

    $perfil = $auth->isAuth();

    // Admitir solo POST
    if($_SERVER["REQUEST_METHOD"] != "POST"):
        $returnData = msg(0, 404, 'La página solicitada no existe!');

    else:

        $rol = $perfil['data']['rol'];

        try{

            $consulta = "SELECT * FROM `sys_menu` WHERE `rol` = :rol AND `deleted` = 0";
            $consulta_stmt = $conn->prepare($consulta);
            $consulta_stmt->bindValue(':rol', $rol, PDO::PARAM_STR);
            $consulta_stmt->execute();

            if($consulta_stmt->rowCount()):

                // Cargar las opciones del rol en un array
                $results = array();
                $data = array();
                $i = 0;
                while($results = $consulta_stmt->fetch(PDO::FETCH_ASSOC)){
                    $data[$i] = $results;
                    $i++;
                }

            
                // Montar arbol de opciones
                function buildTree($data, $rootId=0)
                {
                    $tree = array('submenu' => array(), 'opciones_menu' => array());
                    foreach ($data as $ndx=>$node)
                    {
                        $id = $node['opcion_id'];
                        /* Puede que exista el submenu creado si los hijos entran antes que el padre */
                        $node['submenu'] = (isset($tree['submenu'][$id])) ? $tree['submenu'][$id]['submenu']:array();
                        $tree['submenu'][$id] = $node;

                        if ($node['opcion_padre_id'] == $rootId)
                            $tree['opciones_menu'][$id] = &$tree['submenu'][$id];
                        else
                        {
                            $tree['submenu'][$node['opcion_padre_id']]['submenu'][$id] = &$tree['submenu'][$id];
                        }

                    }
                    return $tree;
                }

                $opciones = buildTree($data);
                unset($opciones['submenu']);

                $returnData = msg(1, 201, 'El rol es ' . $perfil['data']['rol'] , $opciones);

            else:

                $returnData = msg(0, 422, 'Error grave. No se encuentra la definción del menú para el rol ' . $rol);

            endif;

        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage());
        }        

    endif;

}

echo json_encode($returnData);

