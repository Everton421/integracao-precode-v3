<?php
    include_once(__DIR__."/../utils/update-status-order.php");
    include_once(__DIR__."/../services/enviar-notas.php");
    include_once(__DIR__."/../database/conexao_vendas.php");
    include_once(__DIR__."/../database/conexao_integracao.php");


        $integracao = new CONEXAOINTEGRACAO();
        $vendas = new CONEXAOVENDAS();

    $objectSendInvoices = new EnviarNota();
    $resultPutInvoice = $objectSendInvoices->enviar($vendas, $integracao);
        print_r($resultPutInvoice);
?>
