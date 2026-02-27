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

        .mensagem-sucesso i,
        .mensagem-erro i,
        .mensagem-alerta i {
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php
        include_once(__DIR__ . '/../services/kit-produtos/enviar-kit-produto.php');
        include_once(__DIR__ . '/../services/kit-produtos/enviar-preco-kit.php');
        include_once(__DIR__ . '/../services/kit-produtos/enviar-saldo-kit.php');
        include_once(__DIR__ . '/../database/conexao_estoque.php');
        include_once(__DIR__ . '/../database/conexao_publico.php');
        include_once(__DIR__ . '/../database/conexao_vendas.php');

        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            $enviar_kit   = new EnviarKitProduto();
            $enviar_preco = new EnviarPrecoKit();
            $enviar_saldo = new EnviarSaldoKit();

            $integracao = new CONEXAOINTEGRACAO();
            $publico = new CONEXAOPUBLICO();
            $vendas = new CONEXAOVENDAS();
            $estoque = new CONEXAOESTOQUE();

            if (isset($_POST['acao'])) {
                $acao =  $_POST['acao'];

                //  print_r($_POST);




                if ($acao && $acao == 'criar_kit') {

                    print_r($_POST);
                    /*
                    $response = $enviar_kit->enviarkit($_POST);
                    $result = json_decode($response, true);

                    if ($result['success']) {
                        echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                        echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                        echo "<strong> " . $result['message'] . "</strong><br>kit :  " . $_POST['id_kit'];
                        echo '</div>';
                    } else {
                        echo '<div class="mensagem-container mensagem-erro" role="alert">';
                        echo '<i class="fas fa-exclamation-triangle"></i>'; // Ícone de erro (Font Awesome)
                        echo "<strong>Atenção!</strong> " . $result['message'];
                        echo "<br><strong> kit : </strong>" . $_POST['id_kit'];
                        echo '</div>';
                    } */
                }


                if ($acao == 'atualizarPreco') {
                    // Lógica para enviar o produto
                    if (isset($_POST['itens_selecionados']) && is_array($_POST['itens_selecionados'])) {
                        $codigosProdutos = $_POST['itens_selecionados'];
                        foreach ($codigosProdutos as $codigo) {
                            $response = $enviar_preco->postPrecoKit($codigo, $publico, $integracao);

                            $result = json_decode($response, true);
                            if ($result['success'] > 0) {
                                echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                                echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                                echo "<strong> " . $result['message'] . "</strong><br>kit :  $codigo ";
                                echo '</div>';
                            } else {
                                echo '<div class="mensagem-container mensagem-erro" role="alert">';
                                echo '<i class="fas fa-exclamation-triangle"></i>'; // Ícone de erro (Font Awesome)
                                echo "<strong>Atenção!</strong> " . $result['message'];
                                echo "<br><strong> kit: </strong>" . $codigo;
                                echo '</div>';
                            }
                        }
                    }
                }
                if ($acao == 'atualizarEstoque') {
                    if (isset($_POST['itens_selecionados']) && is_array($_POST['itens_selecionados'])) {
                        $codigosProdutos = $_POST['itens_selecionados'];
                        foreach ($codigosProdutos as $codigo) {
                            // Lógica para enviar o produto
                            $response = $enviar_saldo->postSaldoKit($codigo, $publico, $estoque, $vendas, $integracao);

                            $result = json_decode($response, true);
                            if ($result['success'] > 0) {
                                echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                                echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                                echo "<strong> " . $result['message']  . "</strong><br>Kit :  $codigo ";
                                echo '</div>';
                            } else {
                                echo '<div class="mensagem-container mensagem-erro" role="alert">';
                                echo '<i class="fas fa-exclamation-triangle"></i>'; // Ícone de erro (Font Awesome)
                                echo "<strong>Atenção!</strong> " . $result['message'];
                                echo "<br><strong> kit: </strong>" . $codigo;
                                echo '</div>';
                            }
                        }
                    }
                }


                //-------------------  
                /*
            if (isset($_POST['codprod']) && is_array($_POST['codprod'])) {
                $codigosProdutos = $_POST['codprod'];
                foreach ($codigosProdutos as $codigo) {
                      print_r($acao);
                    if ($acao == 'atualizarPreco') {
                        // Lógica para enviar o produto
                       
                        $response = $objEnviarPreco->postPreco($codigo, $publico, $integracao);
                    
                        $result = json_decode($response, true);
                        if ($result['success'] > 0) {
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
                        if ($acao == 'atualizarEstoque') {
                            // Lógica para enviar o produto
                            $response = $objEnviarEstoque->postSaldo($codigo , $publico, $estoque, $vendas, $integracao);
                            $result = json_decode($response, true);
                            if ($result['success'] > 0 ) {
                                echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                                echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                                echo "<strong> " . $result['message']  . "</strong><br>produto :  $codigo ";
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
                         $vinculo = $objeObterVinculo->getVinculo($codigo);  
                         $vinculo =json_decode($vinculo);
                        if($vinculo->success){
                            echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                            echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                            echo "<strong> " . $vinculo->message  . "</strong><br>produto :  $codigo ";
                            echo '</div>';
                        }else{
                            echo '<div class="mensagem-container mensagem-erro" role="alert">';
                            echo '<i class="fas fa-exclamation-triangle"></i>'; // Ícone de erro (Font Awesome)
                            echo "<strong>Atenção!</strong> " . $vinculo->message;
                            echo "<br><strong> Produto: </strong>" . $codigo;
                            echo '</div>';
                         }
                        }  
                }
              
            } else {
                 if($acao == 'vincularTodos'){
                        $resultItems = $publico->consulta("SELECT * FROM cad_prod cp where cp.ATIVO='S' ");
                         $numRows = mysqli_num_rows($resultItems);
                            if($numRows > 0 )  {
                                while ($list = mysqli_fetch_array($resultItems, MYSQLI_ASSOC)) {
                                    sleep(1);
                                      $codigo = $list['CODIGO'];
                                     $vinculo = $objeObterVinculo->getVinculo($codigo);  
                                     $vinculo =json_decode($vinculo);
                               if($vinculo->success){
                                    echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                                    echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                                    echo "<strong> " . $vinculo->message  . "</strong><br>produto :  $codigo ";
                                    echo '</div>';
                                }else{
                                    echo '<div class="mensagem-container mensagem-erro" role="alert">';
                                    echo '<i class="fas fa-exclamation-triangle"></i>'; // Ícone de erro (Font Awesome)
                                    echo "<strong>Atenção!</strong> " . $vinculo->message;
                                    echo "<br><strong> Produto: </strong>" . $codigo;
                                    echo '</div>';
                                }
                                }
                            }
                    }
            }*/
            } else {
                echo "<p class='mensagem-container mensagem-alerta'><i class='fas fa-info-circle'></i> Nenhuma ação especificada.</p>";
            }
        } else {
            echo "<p class='mensagem-container mensagem-alerta'><i class='fas fa-info-circle'></i> Formulário não enviado.</p>";
        }
        //$publico->Desconecta();

        ?>
    </div>
    <!-- Adicione Font Awesome para os ícones -->
    <script src="https://kit.fontawesome.com/YOUR_KIT_ID.js" crossorigin="anonymous"></script>
</body>

</html>