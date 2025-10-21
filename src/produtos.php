 <html>
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
    <title>Precode</title>
</head>
<body>
</body>
<?php 
include_once(__DIR__.'/utils/enviar-produto.php');
 // Verifique se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $objEnviarProduto = new EnviarProduto();

  // Verifique se o array 'codprod' existe no $_POST
  if (isset($_POST['codprod']) && is_array($_POST['codprod'])) {

    // Obtenha o array de códigos de produtos selecionados
    $codigosProdutos = $_POST['codprod'];

     foreach ($codigosProdutos as $codigo) {    
        $response = $objEnviarProduto->enviarProduto($codigo);
        $result = json_decode($response, true );

        if( $result['success']){
            echo '<div class="alert alert-success" role="alert">';
            echo '<strong>Sucesso!</strong> O produto '.$codigo.' foi enviado para a plataforma com sucesso!';
            echo "<strong> ".$result['message']."</strong> <br>" ;
            echo '</div>';
        }else{
            echo '<div class="alert alert-warning" role="alert">';
            echo "<strong>Atenção!</strong>".$result['message'] ;
            echo "<br><strong> Produto: </strong>".$codigo ;
            echo '<br>';
            echo '</div>';
        }        

        }

    
  } else {  
    echo "<p>Nenhum produto selecionado.</p>";
  }
} else {
  echo "<p>Formulário não enviado.</p>";
}

?>