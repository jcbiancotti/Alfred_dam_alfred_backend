<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET,POST,PUT");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__.'/../classes/Database.php';
require __DIR__.'/../middlewares/Auth.php';

$allHeaders = getallheaders();
$db_connection = new Database();
$conn = $db_connection->dbConnection();
$auth = new Auth($conn, $allHeaders);

set_time_limit(0);  // 0 = sin limite de tiempo

$returnData = [
    "success" => 0,
    "status" => 401,
    "message" => "No autorizado"
];

if(true){

    function msg($success, $status, $message, $extra = []){
        return [
            'success' => $success,
            'status' => $status,
            'message' => $message,
            'data' => $extra];
    }

    // GET DATA FORM REQUEST
    $datos = json_decode(file_get_contents("php://input"), true);
    $returnData = [];
    
    // IF REQUEST METHOD IS NOT POST
    if($_SERVER["REQUEST_METHOD"] != "POST"):
        $returnData = msg(0, 404, 'La página solicitada no existe!');

    else:


        try{

            // VACIAR LA TABLA EXISTENTE
            //$query_consulta = "TRUNCATE TABLE callejero";
            //$consulta_stmt = $conn->prepare($query_consulta);
            //$consulta_stmt->execute();


            $fila = 0;
            $insert_query = "INSERT INTO callejero (cod_prov, nom_prov, cod_postal, cod_ine, nom_muni, tipo_via, nom_via) VALUES (";
            $insert_query .= ":cod_prov, :nom_prov, :cod_postal, :cod_ine, :nom_muni, :tipo_via, :nom_via)";

            $insert_stmt = $conn->prepare($insert_query);


            if (($gestor = fopen("callejero1.csv", "r")) !== FALSE) {
                while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {

                    $numero = count($datos);

                    $fila++;
                    //$insert_stmt->bindValue(':clave', $fila,PDO::PARAM_STR);

                    $insert_stmt->bindValue(':cod_prov',   $datos[0], PDO::PARAM_STR);
                    $insert_stmt->bindValue(':nom_prov',   $datos[1], PDO::PARAM_STR);
                    $insert_stmt->bindValue(':cod_postal', $datos[2], PDO::PARAM_STR);
                    $insert_stmt->bindValue(':cod_ine',    $datos[3], PDO::PARAM_STR);
                    $insert_stmt->bindValue(':nom_muni',   $datos[4], PDO::PARAM_STR);
                    $insert_stmt->bindValue(':tipo_via',   $datos[5], PDO::PARAM_STR);
                    $insert_stmt->bindValue(':nom_via',    $datos[6], PDO::PARAM_STR);

                    //if($fila > 37653 && $fila<100000){
                        $insert_stmt->execute();
                    //}

                }
                fclose($gestor);
            }

           
            $returnData = msg(0, 200, 'Lectura del fichero e importación OK, Filas importadas ' . $fila);
           
        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $e->getMessage());
        }

    endif;

}

echo json_encode($returnData);
