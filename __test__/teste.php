<?php
    include_once(__DIR__."/../utils/update-status-order.php");

    $updateStatusOrder = new UpdateStatusOrder();

        $resultUpdateStatusOrder = $updateStatusOrder->put(125230,'aprovado');
        print_r($resultUpdateStatusOrder);
 
?>
