
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="../assets/css/fotos.css" type="text/css">
    <link rel="icon" href="Favicon.png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Precode</title>
    <style>
        .mensagem-container {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 5px;
        }

        .mensagem-sucesso {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .mensagem-erro {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .mensagem-alerta {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .mensagem-sucesso i, .mensagem-erro i, .mensagem-alerta i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
<?php
include_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../utils/enviar-preco.php');

$publico = new CONEXAOPUBLICO();    

$postPreco = new EnviarPreco();
   
$buscaProdutos = $publico->Consulta("SELECT codigo_site,saldo_enviado, codigo_bd, data_recad, data_recad_estoque FROM produto_precode ;" );       

if((mysqli_num_rows($buscaProdutos))  == 0){
     return;
}
 
while($row = mysqli_fetch_array($buscaProdutos, MYSQLI_ASSOC)){
     $codigoBd = $row['codigo_bd'];
     sleep(1);
     
     // 1. Recebe o JSON em texto (ex: {"success":true, ...})
     $jsonRetorno = $postPreco->postPreco($codigoBd, $publico );
     
     // 2. Converte o texto JSON para um Array do PHP
     // O parâmetro 'true' força ser um array associativo
     $resposta = json_decode($jsonRetorno, true);

     // Verifica se o JSON foi decodificado corretamente
     if ($resposta) {
        // Define a classe CSS e o ícone com base no sucesso ou erro
        if ($resposta['success'] === true) {
            $classeCss = 'mensagem-sucesso';
            $icone = 'fa-check-circle';
            $titulo = 'Sucesso!';
        } else {
            $classeCss = 'mensagem-erro';
            $icone = 'fa-exclamation-triangle';
            $titulo = 'Atenção!';
        }
        
        // Exibe a mensagem tratada
        echo '<div class="mensagem-container ' . $classeCss . '" role="alert">';
            echo '<i class="fas ' . $icone . '"></i>';
            echo "<strong>" . $titulo . "</strong> " . $resposta['message'];
            echo "<br><small><strong>Produto ID: </strong>" . $codigoBd . "</small>";
        echo '</div>';
     } else {
         // Caso o retorno não seja um JSON válido
         echo '<div class="mensagem-container mensagem-erro" role="alert">';
            echo "Erro ao ler resposta do servidor para o produto " . $codigoBd;
            // Opcional: mostrar o retorno original para debug
            // echo "<br>" . $jsonRetorno;
         echo '</div>';
     }
}
$publico->Desconecta();
?>




<!-- Adicione Font Awesome para os ícones -->
<script src="https://kit.fontawesome.com/YOUR_KIT_ID.js" crossorigin="anonymous"></script>
</body>
</html>