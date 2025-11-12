<?php
// enviar-foto.php

include_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../database/conexao_vendas.php');

class EnviarFotos {

    public function enviarFotos(int $codigo) {
        $vendas = new CONEXAOVENDAS();
        $publico = new CONEXAOPUBLICO();

        $resultSistemImgsPath = $vendas->Consulta("SELECT FOTOS from parametros WHERE id = 1");

        $sistemImgsPath = mysqli_fetch_array($resultSistemImgsPath, MYSQLI_ASSOC);

         $resultPhotosProd = $publico->Consulta('SELECT * FROM fotos_prod WHERE PRODUTO = '.$codigo );
         $ini = parse_ini_file(__DIR__ . '/../conexao.ini', true);
         $key = $ini['fotos']['key_imgbb'];
         $arrResult =[];

           if( mysqli_num_rows($resultPhotosProd) > 0  ){

                    while( $row = $resultPhotosProd->fetch_assoc()){
                        $photoName = $row['FOTO'];
                        $sequenc = $row['SEQ'];
                        $imagePath = $sistemImgsPath['FOTOS'].''.$row['FOTO'];
                
                        // Limite de tamanho em bytes (ex: 10MB)
                        $maxFileSize = 32 * 1024 * 1024;

                        // Verifica se o arquivo existe
                        if (!file_exists($imagePath)) {
                            return ['success' => false, 'message' => "Arquivo de imagem não encontrado: " . $imagePath];
                        }

                        // Obtém o tamanho do arquivo
                        $fileSize = filesize($imagePath);

                        // Verifica se o tamanho do arquivo excede o limite
                        if ($fileSize > $maxFileSize) {
                            return ['success' => false, 'message' => "O arquivo de imagem excede o tamanho máximo permitido (" . $maxFileSize . " bytes)."];
                        }

                        // Lê o conteúdo da imagem e codifica em base64
                        $imgData = file_get_contents($imagePath);
                        $img = base64_encode($imgData);

                        $arr = array("image" => $img);
                        $curl = curl_init();
                        

                        curl_setopt($curl, CURLOPT_URL, 'https://api.imgbb.com/1/upload?key=' . $key);
                        curl_setopt($curl, CURLOPT_POST, 1);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $arr);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                        $response = curl_exec($curl);

                        if (curl_errno($curl)) {
                             error_log("Erro cURL: " . curl_error($curl)); // Log do erro
                            return ['success' => false, 'message' => "Erro cURL: " . curl_error($curl)];
                        }

                        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                        curl_close($curl);
                        $retorno = json_decode($response, true);

                        if ($httpCode == 200) {
                            //return $retorno;
                            $link =  $retorno['data']['url'];
                            array_push( $arrResult, $link );

                                    $oldPhotos = $publico->Consulta("SELECT * FROM fotos_prod_precode WHERE produto = '$codigo' AND SEQ ='$sequenc';");
                                    if(mysqli_num_rows($oldPhotos) > 0  ){
                                    $resultDelete = $publico->Consulta("DELETE  FROM fotos_prod_precode WHERE PRODUTO =  '$codigo' AND SEQ ='$sequenc';");
                                    }
                                    $resultInsert =  $publico->Consulta("INSERT INTO fotos_prod_precode SET  PRODUTO = '$codigo', FOTO = '$photoName',SEQ='$sequenc', BASE64_FOTO='$img', LINK='$link' ");
                                    if( $resultInsert   ){
                                    }  else{
                                        error_log("Erro ao registrar foto no banco de dados: " . $publico->conexao->error); // Log do erro
                                        return ['success' => false, 'message' => "erro ao registrar foto no banco de dados : HTTP Code: " . $httpCode . " - " . $publico->conexao->error];
                                    }     
                        } else {
                            error_log("Erro ao enviar imagem para o ImgBB: HTTP Code: " . $httpCode . " - Response: " . $response); // Log do erro
                            return ['success' => false, 'message' => "Erro ao enviar imagem para o ImgBB: HTTP Code: " . $httpCode . " - Response: " . $response];
                        }
                
                    }
                    return $arrResult;
             }else{
                   error_log("Nenhuma imagem foi encontrada para o produto: " . $codigo); // Log do erro
                    return  ['success' => false, 'message' => "nenhuma imagem foi encontrada produto: " .$codigo ];

             }
       $publico->Desconecta();
       $vendas->Desconecta();

     } 

}
?>