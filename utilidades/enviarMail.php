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

        /*$nombre = "Juancito";
        $nroRegistro = "123456";

        $texto = "<p><h1>$nombre,</h1></p><p>Te damos la bienvenida. Estas a un click de completar el registro en la aplicación.</p>";
        $texto .= "<p>Pincha en el siguiente enlace y habremos terminado.</p>";
        $texto .= '<p><a href="http://localhost/dam_alfred_backend/auth/fin_registro.php?correo=' . $txtDestino . '&codigo=' . $nroRegistro . '">Pincha aquí</a></p>';
        $texto .= '<BR>';
        $texto .= '<p>Recibes este correo porque has solicitado registrarte en Alfred.es. Si no has sido tú, ignóralo.</p>';

        $txtCuerpo = $texto;
        */

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        // More headers
        //$headers .= 'From: <webmaster@example.com>' . "\r\n";
        //$headers .= 'Cc: myboss@example.com' . "\r\n";

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


