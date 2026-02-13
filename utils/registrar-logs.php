  <?php
  

  Class Logs{
   
  public static function registrar(
    $connection ,
    $databaseName,
    $status = 'sucesso',
    string | null $acao = null,
    string | null $dados = null,
    string | null $referencia = null,
    string | null $mensagem = null
  ){

    $sql = "INSERT INTO `$databaseName`.`log_precode` SET `acao`='$acao',`dados`= "."\"$dados\"".", `status`='$status',`referencia`='$referencia', `mensagem`='$mensagem' ;";

    $result =  $connection->Consulta($sql);

        return $result;
  }
 
  }
    ?>