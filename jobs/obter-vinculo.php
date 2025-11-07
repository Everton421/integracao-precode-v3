<?php

set_time_limit(0);
include_once(__DIR__.'/../database/conexao_publico');
include_once(__DIR__.'/../utils/obter-vinculo-produto.php');
        $publico = new CONEXAOPUBLICO();
        $objeObterVinculo = new ObterVinculo();

 $resultItems = $publico->consulta("SELECT * FROM cad_prod cp where cp.ATIVO='S' AND cp.NO_MKTP='S'");
                         $numRows = mysqli_num_rows($resultItems);
                            if($numRows > 0 )  {
                                while ($list = mysqli_fetch_array($resultItems, MYSQLI_ASSOC)) {
                                  sleep(1000);
                                    $vinculo = $objeObterVinculo->getVinculo($list['CODIGO']); // Supondo que exista essa função
                                }
                            }

?>