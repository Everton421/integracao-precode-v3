<?php
// busca_detalhes_produto.php
header('Content-Type: application/json; charset=utf-8');

include_once(__DIR__ . '/../database/conexao_publico.php');
include_once(__DIR__ . '/../database/conexao_vendas.php');
include_once(__DIR__ . '/../database/conexao_estoque.php');
include_once(__DIR__ . '/../database/conexao_integracao.php');

$codigoPadr = $_GET['codigo'] ?? '';

if (empty($codigoPadr)) {
    echo json_encode(['erro' => 'Código vazio']);
    exit;
}

$publico = new CONEXAOPUBLICO();
$vendas = new CONEXAOVENDAS();
$estoque = new CONEXAOESTOQUE();
$integracao = new CONEXAOINTEGRACAO();

$databaseEstoque = $estoque->getBase();
$databasePublico = $publico->getBase();
$databaseVendas = $vendas->getBase();
$databaseIntegracao = $integracao->getBase();

 
$sql = "SELECT 
          -- max( k.id ) as id_kit ,
			 cpa.CODIGO as id_kit ,
            p.*, 
            tp.PRECO, tp.PROMOCAO, 
            m.descricao AS MARCA, 
            cf.NCM, 
            cg.NOME AS CATEGORIA 
        FROM cad_prod p
        INNER JOIN prod_tabprecos tp ON p.CODIGO = tp.PRODUTO
        LEFT JOIN cad_pmar m ON m.codigo = p.marca
        LEFT JOIN class_fiscal cf ON cf.CODIGO = p.CLASS_FISCAL
        LEFT JOIN cad_pgru cg ON cg.CODIGO = p.GRUPO
				LEFT JOIN ite_padr itp on itp.PROD_SERV = p.CODIGO	
				LEFT JOIN cad_padr cpa on cpa.CODIGO = itp.PADRONIZADO
        WHERE cpa.CODIGO = '$codigoPadr';";

$result = $publico->Consulta($sql);
$produto = mysqli_fetch_array($result, MYSQLI_ASSOC);

if ($produto) {
    // Converte UTF8
    foreach ($produto as $k => $v) {
        $produto[$k] = mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1');
    }

    $lastId = 0;

    if($produto['id_kit'] && $produto['id_kit'] != '' && $produto['id_kit'] != null ){
        $lastId = $produto['id_kit'];
    }
        
            $newId = $lastId + 1;
    // Recupera valores atuais (ou vazio se nulo)
         $descricao   = $produto['DESCRICAO'] ?? '';

         $catFinal ='';

         $catPrincipal = 'Acessórios para Veículos';
    
         $catInterm = 'Peças de Carros e Caminhonetes';

        // Verifica ANÉIS (mb_stripos é case-insensitive e aceita acentos)
        if (mb_stripos($descricao, 'ANEL') !== false || mb_stripos($descricao, 'ANEIS') !== false || mb_stripos($descricao, 'ANÉIS') !== false) {
            $catFinal = 'Anéis Segmento';
        }
        // Verifica BRONZINA
        elseif (mb_stripos($descricao, 'BRONZINA') !== false) {
            // Pode refinar aqui: Biela ou Mancal?
            if (mb_stripos($descricao, 'BIELA') !== false) {
                 $catFinal = 'Bronzina de Biela';
            } else {
                 $catFinal = 'Bronzina de Mancal';
            }
        }
        // Adicione aqui outras validações (Ex: Pistão, Junta, etc)
        elseif (mb_stripos($descricao, 'PISTAO') !== false || mb_stripos($descricao, 'PISTÃO') !== false) {
            $catFinal = 'Pistão de Motor';
        }

    // Atualiza o array do produto com os valores sugeridos
    $produto['CATEGORIA_MKTPLACE'] = $catPrincipal;
    $produto['INTERM_CATEGORIA_MKTPLACE'] = $catInterm; // Nova chave para o JSON
    $produto['FINALCATEGORIA_MKTPLACE'] = $catFinal;
    // Lógica simples de Estoque (ou use a complexa anterior se preferir)
    $produto['ESTOQUE_REAL'] = 0; // Padrão caso não ache
       $produto['id_kit']  =  "KIT-$newId";
    
    // Formatar Origem para Texto
    $origemMap = ['0'=>'Nacional', '1'=>'Importado', '2'=>'Importado', '3'=>'Nacional', '8'=>'Nacional'];
    $produto['ORIGEM_TEXTO'] = $origemMap[$produto['ORIGEM']] ?? 'Nacional';

    echo json_encode($produto);
} else {
    echo json_encode(['erro' => 'Produto não encontrado']);
}
?>