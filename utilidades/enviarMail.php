<?php
class AlfredMails{
    
    function msg($success,$status,$message,$extra = []){
        return array_merge([
            'success' => $success,
            'status' => $status,
            'message' => $message
        ],$extra);
    }

    public function emEnviar($txtDestino, $txtAsunto, $txtCuerpo){

        // Datos del email
        $headers = "From:alf.correos@gmail.com". "\r\n";
        $headers .= "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $mensaje = "<html><head><title>" . $txtAsunto . "</title></head><body>" . $txtCuerpo . "</body></html>";

        try{

            // send email
            $enviado = mail($txtDestino, $txtAsunto, $txtCuerpo, $headers);

            $returnData = $this->msg(0, 200, $enviado);

        }
        catch(PDOException $e){
            $returnData = $this->msg(0, 500, $e->getMessage());
        }

    }

}


// echo json_encode("Correo enviado a " . $_GET['correo'] . ', Asunto: Registro en la aplicación , Codigo: ' . $_GET['codigo'] . ' texto: texto ');

?>


