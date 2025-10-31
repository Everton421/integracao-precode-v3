<?php
    include_once(__DIR__.'/../database/conexao_publico.php');


 Class ObterVinculo{

       function getVinculo(  int $codigo ){
        

        $publico = new CONEXAOPUBLICO();
        $ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);

            $resultQueryProd = $publico->consulta("SELECT p.OUTRO_COD AS referencia from cad_prod as p where p.CODIGO = $codigo;");
    if(mysqli_num_rows($resultQueryProd ) == 0)  {
             echo  "<strong> </strong><br>  produto :  $codigo nao existe ";
          return;
        }
    while($row = mysqli_fetch_array($resultQueryProd, MYSQLI_ASSOC)){
            $referencia = $row['referencia'];
    }
    
        
    if(empty($referencia ) || $referencia == ''){
         echo  "<strong> </strong><br>o produto :  $codigo esta sem referencia";
        return;
        }

        if(empty($ini['conexao']['token'] )){
          echo 'token da aplicação não fornecido';
            return $this->response(false, 'token da aplicação não fornecido');
        }    

            $token = $ini['conexao']['token'];

          $curl = curl_init();
         curl_setopt_array($curl, array(
         CURLOPT_URL => "https://www.replicade.com.br/api/v3/products/query/".$referencia."/ref",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: ".$token
        ),
        ));
        $response = curl_exec($curl);
        $result = json_decode($response);    
         $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);                    

        curl_close($curl);  
            if($httpcode != 200  ){
           
                      echo '<div class="mensagem-container mensagem-erro" role="alert">';
                            echo '<i class="fas fa-exclamation-triangle"></i>'; // Ícone de erro (Font Awesome)
                            if(!empty($result->mensagem)){ 
                             echo "<strong>Atenção!</strong> " .$result->mensagem ;
                            }else{
                                print_r($result);
                            }
                            echo "<br><strong> Produto: </strong>" . $codigo.'  Não foi encontrado no precode';
                            echo '</div>';
                     return;
             }
        
        if(!empty($result)){  
            $idPrecode = $result->produto->codigoAgrupador;

            $validationProduct = $publico->consulta("SELECT * FROM produto_precode WHERE CODIGO_SITE= '$idPrecode'  AND CODIGO_BD='$codigo'; ");
            if ((mysqli_num_rows($validationProduct)) == 0) {
              $result =  $publico->consulta("INSERT INTO produto_precode ( CODIGO_SITE , CODIGO_BD )
                VALUES( '$idPrecode', '$codigo' );");
                    if($result == 1 ){
                     echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                     echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                     echo "<strong> </strong><br>Obtido vinculo para o produto :  $codigo ";
                     echo '</div>';
                    }
            }else{

                while($row = mysqli_fetch_array($validationProduct, MYSQLI_ASSOC)){
                        $codigoPrecode = $row['CODIGO_SITE'];
                }
                     echo '<div class="d-flex flex-row mensagem-container mensagem-sucesso" role="alert">';
                     echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                     echo "   O Produto Informado já Possui um Vinculo  |  <strong> produto ERP Cód:  $codigo | referencia: $referencia  | Cód precode :  $codigoPrecode  </strong>";
                     echo '</div>';
                }
            

        }
        
        
     }





     private function response(bool $success, string $message, $data = null): string {
        return json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
    }

    
}

?>
