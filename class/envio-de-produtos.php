<?php
require_once('precode.php');

$successMessage = '';
$errorMessage = '';

if (!empty($_POST['produtoId'])){
    $productId = $_POST['produtoId'];
    
    $precode = new Precode();

    $enviaProduto = $precode->sendProduct($productId);

    if ($enviaProduto['sendProduct']) {
        $httpCode = $enviaProduto['httpCode'];
        if ($httpCode == 200 || $httpCode == 200) {
            $successMessage = 'Seu produto foi cadastrado com sucesso!';
            $_POST['produtoId'] = ''; // Limpa o campo de entrada
        } else {
            $errorMessage = 'Houve uma falha ao cadastrar seu produto! O código de retorno é ' . $httpCode . ' ' . $enviaProduto['response']['message'];
        }
    } else {
        $errorMessage = 'Houve uma falha ao cadastrar seu produto! O código de retorno é ' . $enviaProduto['httpCode'] . ', erro na montagem do produto: ' . $enviaProduto['msg'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php if (!empty($successMessage)): ?>
        <p><?php echo $successMessage; ?></p>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <p><?php echo $errorMessage; ?></p>
    <?php endif; ?>

    <form action="" method="POST">
        <label for="produtoId">Código do produto</label>
        <input type="text" id="produtoId" name="produtoId" value="<?php echo $_POST['produtoId'] ?? ''; ?>">
        <input type="submit" name="submit" value="Enviar">
    </form>

    <script>
        // Limpa os campos e recarrega a página após o envio do formulário
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        } else {
            window.location.href = window.location.href;
        }
    </script>
</body>
</html>
