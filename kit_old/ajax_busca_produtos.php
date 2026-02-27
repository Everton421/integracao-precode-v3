<?php
// ajax_busca_produtos.php
header('Content-Type: application/json; charset=utf-8');
include_once(__DIR__ . '/../database/conexao_publico.php');

$termo = $_GET['termo'] ?? '';
if (strlen($termo) < 3) exit(json_encode([]));

$publico = new CONEXAOPUBLICO();
//$termoSafe = mysqli_real_escape_string($publico->getConnection(), $termo);

$sql = "SELECT CODIGO, DESCRICAO FROM cad_padr 
        WHERE (CODIGO LIKE '%$termo%' OR DESCRICAO LIKE '%$termo%') 
        AND PROD_SERV='P' LIMIT 10";

$res = $publico->Consulta($sql);
$lista = [];
while($row = mysqli_fetch_assoc($res)){
    $lista[] = [
        'codigo' => mb_convert_encoding($row['CODIGO'], 'UTF-8', 'ISO-8859-1'),
        'descricao' => mb_convert_encoding($row['DESCRICAO'], 'UTF-8', 'ISO-8859-1')
    ];
}
echo json_encode($lista);
?>