<?php

include_once(__DIR__."/../utils/obter-vinculo-produto.php");
include_once(__DIR__."/../utils/enviar-saldo.php");
include_once(__DIR__."/../utils/obter-etiqueta.php");

     $obj = new ObterEtiqueta();
     $obj->getEtiquetas(124483);

/*
    if( $_SERVER["REQUEST_METHOD"] == "POST") {
        if(isset($_POST['codprod'])){  // Check if 'codprod' key exists

            $codigosSelecionados = $_POST['codprod'];

            //echo "<h2>Códigos de Produto Selecionados:</h2>";
            //echo "<ul>";
            //foreach($codigosSelecionados as $codigo) {
            //    echo "<li>" . htmlspecialchars($codigo) . "</li>"; // Use htmlspecialchars for security
            //}
            echo "</ul>";

            //  Aqui você pode processar os códigos, como buscar informações no banco de dados,
            //  gerar etiquetas, etc.
            //  Exemplo:
            //  foreach($codigosSelecionados as $codigo) {
            //      $dadosDoProduto = buscarDadosDoProduto($codigo);
            //      gerarEtiqueta($dadosDoProduto);
            //  }

        } else {
           // echo "<p>Nenhum código de produto foi selecionado.</p>";
        }
    } else {
        echo "<p>Este script só aceita requisições POST.</p>";
    }
    */

 ?>