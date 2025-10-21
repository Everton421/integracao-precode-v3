<?php
include(__DIR__.'/../utils/enviar-foto.php'); // Inclua o arquivo com a classe EnviarFotos

$enviarFotos = new EnviarFotos();

try {
    $resposta = $enviarFotos->enviarFotos(161); // Substitua 123 pelo código apropriado
    
    // Se a resposta for bem-sucedida, exiba o URL da imagem
     if (isset($resposta )){
          print_r( $resposta );
     } else {
         echo "Erro inesperado na resposta da API ImgBB.";
        // print_r($resposta); // Imprime a resposta completa para depuração
     }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>