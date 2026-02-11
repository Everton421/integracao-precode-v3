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
    include_once(__DIR__.'/../utils/enviar-produto-grade.php');
    include_once(__DIR__.'/../utils/obter-vinculo-produto.php');
    include_once(__DIR__.'/../database/conexao_publico.php');
    include_once(__DIR__.'/../database/conexao_vendas.php');
    include_once(__DIR__.'/../database/conexao_estoque.php');

    include_once(__DIR__.'/../utils/enviar-preco.php');
    include_once(__DIR__.'/../utils/enviar-saldo.php');

    include_once(__DIR__.'/../mapper/produto-grade-mapper.php');


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $objeObterVinculo = new ObterVinculo();
        $publico= new CONEXAOPUBLICO();
        $estoque= new CONEXAOESTOQUE();
        $vendas= new CONEXAOVENDAS();
        $objEnviarPreco = new EnviarPreco();
        $objEnviarEstoque = new EnviarSaldo();
        $enviarProdutoGrade = new EnviarProdutoGrade();

        $mapper = new ProdutoGradeMapper($publico, $vendas, $estoque);

          //  print_r($_POST);
 
            
        if (isset($_POST['acao'])) {
            $acao = $_POST['acao'];

                if($acao == 'enviar'){
                 $response = $enviarProdutoGrade->enviar($_POST);
                        $result = json_decode($response, true);
               

                        if ($result['success']) {
                            echo '<div class="mensagem-container mensagem-sucesso" role="alert">';
                            echo '<i class="fas fa-check-circle"></i>'; // Ícone de sucesso (Font Awesome)
                            echo "<strong> " . $result['message'] . "</strong><br>produto :  ".$_POST['codigo']  ;
                            echo '</div>';
                        } else {
                            echo '<div class="mensagem-container mensagem-erro" role="alert">';
                            echo '<i class="fas fa-exclamation-triangle"></i>'; // Ícone de erro (Font Awesome)
                            echo "<strong>Atenção!</strong> " . $result['message'];
                            echo "<br><strong> Produto: </strong>" . $_POST['codigo'] ;
                            echo '</div>';
                        }
                }

                if($acao == 'enviar_lista'){
                    $codgrade = $_POST['codgrade'];

                    $sucessos = 0;
                    $erros = 0;

                    foreach($codgrade as $cod ){

                             $dadosParaEnvio = $mapper->obterDadosParaEnvio($cod);
                             if($dadosParaEnvio){
                                try{
                                    $jsonResponse = $enviarProdutoGrade->enviar($dadosParaEnvio);
                                    $resposta = json_decode($jsonResponse , true);
                                        if($resposta['success']){
                                        echo "<span style='color:green'>SUCESSO: " . $resposta['message'] . "</span>\n";
                                        $sucessos++;
                                        } 
                                        if(!$resposta['success']){
                                            echo "<span style='color:red'>ERRO API: " . $resposta['message'] . "</span>\n";
                                            $erros++;
                                        }
                                }catch(Exception $e ){
                                   echo "<span style='color:red'>EXCEÇÃO: " . $e->getMessage() . "</span>\n";
                                     $erros++;
                                }

                            }else{
                               echo "<span style='color:orange'>ALERTA: Dados incompletos ou produto não encontrado.</span>\n";
                               $erros++;
                            }
                            
                           usleep(500000); 
                    }
                    echo "\n------------------------------------------------";
                    echo "\nResumo: Sucessos: $sucessos | Erros: $erros";
                    echo "</pre>";
                }
           
        
        } else {
            echo "<p class='mensagem-container mensagem-alerta'><i class='fas fa-info-circle'></i> Nenhuma ação especificada.</p>";
        }  


    } else {
        echo "<p class='mensagem-container mensagem-alerta'><i class='fas fa-info-circle'></i> Formulário não enviado.</p>";
    }
        $publico->Desconecta();
        $estoque->Desconecta();
        $vendas->Desconecta();

    ?>
</div>
<!-- Adicione Font Awesome para os ícones -->
<script src="https://kit.fontawesome.com/YOUR_KIT_ID.js" crossorigin="anonymous"></script>
</body>
</html>