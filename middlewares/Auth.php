<?php
require __DIR__.'/../classes/JwtHandler.php';
class Auth extends JwtHandler{

    protected $db;
    protected $headers;
    protected $token;
    public function __construct($db,$headers) {
        parent::__construct();
        $this->db = $db;
        $this->headers = $headers;
    }

    public function isAuth(){

        if(array_key_exists('Authorization', $this->headers) && !empty(trim($this->headers['Authorization']))):
            
            $this->token = explode(" ", trim($this->headers['Authorization']));
            if(isset($this->token[1]) && !empty(trim($this->token[1]))):
                $data = $this->_jwt_decode_data($this->token[1]);
                if(isset($data['auth']) && isset($data['data']->user_id) && $data['auth']):
                    $user = $this->fetchUser($data['data']->user_id);
                    return $user;
                else:
                    return null;
                endif; // End of isset($this->token[1]) && !empty(trim($this->token[1]))
            else:
                return null;
            endif;// End of isset($this->token[1]) && !empty(trim($this->token[1]))

        else:
            return null;

        endif;
    }

    protected function fetchUser($user_id){

        try{
            $fetch_user_by_id = "SELECT `clave`, `nombre`, `nombre_completo`, `email`, 1 as `autorizado`, `es_admin`, `es_admin_grupos` FROM `usuarios` WHERE `clave` = :id AND `deleted` = 0";
            $query_stmt = $this->db->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
            $query_stmt->execute();

            if($query_stmt->rowCount()):

                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                
                $row['rol'] = $this->GetRol($user_id, $row['es_admin'], $row['es_admin_grupos']);
                $row['padre'] = $this->GetPadre($user_id);
                $row['abuelo'] = $this->GetPadre($row['padre']);
                
                return [
                    'success' => 1,
                    'status' => 200,
                    'data' => $row
                ];
            else:
                return null;
            endif;
        }
        catch(PDOException $e){
            return null;
        }
    }

    protected function GetRol($user_id, $esAdmin, $esAdminGrupos){
    
        try{

            $fetch_user_by_id = "SELECT `padre` FROM `rel_usr_grupos` WHERE `clave_usuario` = :id AND `deleted` = 0";
            $query_stmt = $this->db->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
            $query_stmt->execute();

            $es_cli = 'NO ROL';

            if($query_stmt->rowCount()):

                $results = array();
                while($results = $query_stmt->fetch(PDO::FETCH_ASSOC)){
                    if($results['padre'] == 1):
                        $es_cli = 'CLIENTE';
                    endif;
                }

            endif;

            if($es_cli == 'CLIENTE'):
                return $es_cli;
            elseif($esAdmin == 1):
                return 'ADMIN';
            elseif($esAdminGrupos == 1):
                return 'ADMINGRP';
            else:
                return 'GESTOR';
            endif;
            

        } catch(PDOException $e){
            return 'ROL ERROR';
        }
    
    }

    protected function GetPadre($user_id){
    
        try{

            $fetch_user_by_id = "SELECT `padre` FROM `rel_usr_grupos` WHERE `clave_usuario` = :id AND `deleted` = 0";
            $query_stmt = $this->db->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
            $query_stmt->execute();

            $es_padre = 0;

            if($query_stmt->rowCount()):

                $results = array();
                while($results = $query_stmt->fetch(PDO::FETCH_ASSOC)){
                    $es_padre = $results['padre'];
                }

            endif;
            return $es_padre;
            

        } catch(PDOException $e){
            return 'ROL ERROR';
        }
    
    }    
}
