<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


//////////////////////////////////////////////////////////////////////////////////////////////////////////// //
// Para poder descargar el PDF en la visualización del nevagador (Chrome) debe estar instalada la extensión  //
// PDF Viewer Plugin                                                                                         //
///////////////////////////////////////////////////////////////////////////////////////////////////////////////

require __DIR__.'/../classes/Database.php';
require __DIR__.'/../middlewares/Auth.php';
require __DIR__.'/fpdf.php';

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
    elseif(!isset($datos['titulo'])
        || !isset($datos['orientacion'])
        || !isset($datos['query'])
        || !isset($datos['columnas'])
        || empty(trim($datos['titulo']))
        || empty(trim($datos['orientacion']))
        || empty(trim($datos['query']))
        || empty($datos['columnas'])
        ):

        $fields = ['fields' => ['titulo', 'orientacion', 'query', 'columnas[]','filtros[] (opcional)']];
        $returnData = msg(0, 422, 'Faltan datos necesarios!', $fields); 

    // Si se han recibido los datos necesarios
    else:
        //////////////// VALORES RECIBIDOS ///////////////////////////////////////////////
        $columnas = array();
        $titulo = trim($datos['titulo']);
        $orientacion = trim($datos['orientacion']);
        $Qclave = trim($datos['query']);
        $columnas = $datos['columnas'];

        if(isset($datos['filtros'])) {
            $filtros = $datos['filtros'];
        } else {
            $filtros = [];
        }

        if($orientacion == 'V') {
            $orientacion = 'P';
        }else {
            $orientacion = 'L';
        }

        $req_query = "SELECT * FROM `sys_querys` WHERE clave = :clave and deleted = 0" ;

        //////////////// BUSCAR LA CONSULTA //////////////////////////////////////////////
        try{

            $query_stmt = $conn->prepare($req_query);
            $query_stmt->bindValue(':clave', $Qclave, PDO::PARAM_STR);
            $query_stmt->execute();

            if($query_stmt->rowCount()):

                $results = array();
                $results = $query_stmt->fetch(PDO::FETCH_ASSOC);

                //////////////// EJECUTAR LA CONSULTA //////////////////////////////////////////////
                $query_consulta = $results['consulta'];
                $consulta_stmt = $conn->prepare($query_consulta);

                // Completar los filtros
                if(count($filtros) > 0) {

                    foreach($filtros as $filtro) {
                        $consulta_stmt->bindValue(':' . $filtro['field'], $filtro['valor'], PDO::PARAM_STR);
                    }

                }
                // Ejecutar la consulta
                $consulta_stmt->execute();

                if($consulta_stmt->rowCount()):

                    $consulta_results = array();
                    $data = array();

                    $i = 0;
                    while($consulta_results = $consulta_stmt->fetch(PDO::FETCH_ASSOC)){
                        $data[$i] = $consulta_results;
                        $i++;
                    }
                    //////////////// GENERACION DEL PDF ///////////////////////////////////////////
                    
                    // Clase heredada de FPDF -------------------------------------------------- //
                    class ALF_PDF extends FPDF
                    {
                        // Cabecera de página
                        function Header()
                        {
                            global $titulo, $filtros;

                            // Logo
                            $this->Image('./../assets/img/alfred-logo-header.png', 10, 10, 30);

                            $this->SetFont('Arial', 'B', 15);
                            //$this->Cell(1);
                            $this->Cell(0, 4, $titulo, 0, 0, 'R');
                            $this->Ln(10);

                            // // Cadena de filtros
                            if(count($filtros) > 0) {
                                $cFiltros = "";
                                foreach($filtros as $filtro) {

                                    $xf = $filtro['valor'];
                                    if($filtro['tipo'] == 'date') {

                                        date_default_timezone_set("Europe/Madrid");

                                        $time = strtotime($xf);
                                        //$newformat = date('d-m-Y h:i:s a', $time);
                                        $newformat = date('d-m-Y', $time);
                                        $xf = $newformat;

                                    }
                                    
                                    if($filtro['tipo'] != 'idUsuario') {
                                        $cFiltros .= $filtro['label'] . ": " . $xf . "    ";
                                    }

                                    
                                }
                                $this->SetFont('Arial', '', 10);
                                $this->Cell(0, 4, trim($cFiltros), 0, 0, 'R');
                            }
                            $this->Ln();
                        }
                    
                        // Pie de página
                        function Footer()
                        {
                            date_default_timezone_set("Europe/Madrid");
                            $d = date('d-m-Y h:i:s a', time()); 

                            $this->SetY(-15);
                            $this->SetFont('Arial','I',8);
                            $this->Cell(50, 10, "FECHA: " . $d, 0, 0, 'R');
                            $this->Cell(0, 10, 'Hoja '.$this->PageNo().'/{nb}', 0, 0, 'R');
                        }
                        
                        // Nombre unico del fichero local
                        function getUniqueName()
                        {
                            date_default_timezone_set('UTC');
                            $name = "AlfredDoc_";
                            $name.= date("YmdHis");
                            $name.= substr(md5(rand(0, PHP_INT_MAX)), 10);
                            $name.= ".pdf";
                            return $name;
                        }

                        function ImprovedTable($renglones) 
                        {

                            global $columnas;

                            // Cabeceras
                            $anchoTotal = 0;
                            for($i=0;$i<count($columnas);$i++) {
                                $texto = iconv('UTF-8', 'windows-1252', $columnas[$i]['label']);
                                $this->Cell($columnas[$i]['ancho'], 7, $texto, 1, 0, 'C');
                                $anchoTotal += $columnas[$i]['ancho'];
                            }
                            $this->Ln();

                            // Datos
                            $c = 0;
                            foreach($renglones as $row)
                            {
                                for($t=0;$t<count($columnas);$t++) {
                                    $texto = iconv('UTF-8', 'windows-1252', $row[$columnas[$t]['field']]);
                                    $this->Cell($columnas[$t]['ancho'], 5, $texto, 0, 0, 'L', 0);
                                }
                                $c++;
                                $this->Ln();
                            }
                            // Línea de cierre
                            $this->Cell($anchoTotal,0,'','T');
                        }

                    }
                    // FIN Clase heredada de FPDF ---------------------------------------------- //                    

                    $pdf = new ALF_PDF();
                    $nomFichero = $pdf->getUniqueName();
                    $text = "";

                    $pdf->SetTitle($titulo);
                    $pdf->SetAuthor('(Proyecto DAM) Alfred v1.0');
                    $pdf->SetCreator('Alfred v1.0');
                    $pdf->SetSubject($titulo);

                    $pdf->AliasNbPages();
                    $pdf->AddPage($orientacion);
                    $pdf->SetFont('Arial','',8);
                    $pdf->ImprovedTable($data);
                    $pdf->Output('F', $nomFichero);
                    
                    // Leer y devolver documento
                    $fd = fopen($nomFichero,"r");
                    if ($fd) {
                        while (($myline = fgets($fd)) !== false) {
                            $text .= $myline;
                        }
                    }

                    // Borrar el fichero temporal
                    unlink($nomFichero);

                    $returnData = msg(1, 201, 'Documento generado!', base64_encode($text));

                else:
                    $returnData = msg(1, 422, 'No hay datos para la consulta solicitada!');
                endif;

            else:
                $returnData = msg(0, 422, 'Se está solicitando una consulta que no existe, comprueba que la clave ' . $Qclave . ' exite en la tabla SYS_QUERYS!');
            endif;

        }
        catch(PDOException $e){
            $returnData = msg(0, 500, $query_consulta . '////' . $e->getMessage() );
        }

    endif;

}

echo json_encode($returnData);


