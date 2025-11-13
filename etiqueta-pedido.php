

<?php

include_once(__DIR__."/utils/obter-etiqueta.php");

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
         if (isset($_POST['codpedido'] )) {
           $obj = new ObterEtiqueta();
               $resultEtiq = $obj->getEtiquetas($_POST['codpedido']);
                  $resultEtiq = json_decode($resultEtiq);

              if(!$resultEtiq->success   ){
            ?>

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
            <?php
               echo '<div class="mensagem-container mensagem-erro" role="alert">';
               echo '<i class="fas fa-exclamation-triangle"></i>'; // Ícone de erro (Font Awesome)
               echo "<strong>Atenção!</strong> <br>" . $resultEtiq->message;
               echo '</div>';
              ?>

                <script src="https://kit.fontawesome.com/YOUR_KIT_ID.js" crossorigin="anonymous"></script>
            </body>
        </html>
<?php }
                       

        }
    }
    
?> 