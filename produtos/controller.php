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
<div class="container">
    <?php
    include_once(__DIR__.'/../utils/enviar-produto.php');
    include_once(__DIR__.'/../utils/obter-vinculo-produto.php');
    include_once(__DIR__.'/../database/conexao_publico.php');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $objEnviarProduto = new EnviarProduto();
        $objeObterVinculo = new ObterVinculo();
        $publico= new CONEXAOPUBLICO();

        if (isset($_POST['acao'])) {
            $acao = $_POST['acao'];

            if (isset($_POST['codprod']) && is_array($_POST['codprod'])) {
                $codigosProdutos = $_POST['codprod'];

                foreach ($codigosProdutos as $codigo) {
                    if ($acao == 'enviar') {
                        // Lógica para enviar o produto
                        $response = $objEnviarProduto->enviarProduto($codigo);
                        $result = json_decode($response, true);

                        if ($result['success']) {
                            echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                            echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                            echo "<strong> " . $result['message'] . "</strong><br>produto :  $codigo ";
                            echo '</div>';
                        } else {
                            echo '<div class="mensagem-container mensagem-erro" role="alert">';
                            echo '<i class="fas fa-exclamation-triangle"></i>'; // Ícone de erro (Font Awesome)
                            echo "<strong>Atenção!</strong> " . $result['message'];
                            echo "<br><strong> Produto: </strong>" . $codigo;
                            echo '</div>';
                        }
                    } 
                    if ($acao == 'vincular') {
                         $vinculo = $objeObterVinculo->getVinculo($codigo); // Supondo que exista essa função

                    }  
                     
                }
            } else {
                 if($acao == 'vincularTodos'){
                        $resultItems = $publico->consulta("SELECT * FROM cad_prod cp where cp.ATIVO='S' AND cp.NO_MKTP='S'");
                         $numRows = mysqli_num_rows($resultItems);
                            if($numRows > 0 )  {
                                while ($list = mysqli_fetch_array($resultItems, MYSQLI_ASSOC)) {
                                     $vinculo = $objeObterVinculo->getVinculo($list['CODIGO']); // Supondo que exista essa função
                                }
                            }
                    }else{
                  echo "<p class='mensagem-container mensagem-alerta'><i class='fas fa-info-circle'></i> Nenhum produto selecionado.</p>";
                  }
            }

        } else {
            echo "<p class='mensagem-container mensagem-alerta'><i class='fas fa-info-circle'></i> Nenhuma ação especificada.</p>";
        }
    } else {
        echo "<p class='mensagem-container mensagem-alerta'><i class='fas fa-info-circle'></i> Formulário não enviado.</p>";
    }
    ?>
</div>
<!-- Adicione Font Awesome para os ícones -->
<script src="https://kit.fontawesome.com/YOUR_KIT_ID.js" crossorigin="anonymous"></script>
</body>
</html>