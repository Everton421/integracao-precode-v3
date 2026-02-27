<?php
// Configurações para script de longa execução
set_time_limit(0); 
ini_set('memory_limit', '256M');

include_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../database/conexao_estoque.php'); 
include_once(__DIR__.'/../database/conexao_vendas.php');
include_once(__DIR__.'/../database/conexao_integracao.php');
include_once(__DIR__.'/../database/conexao_eventos.php');

// Utils
include_once(__DIR__.'/../utils/enviar-saldo.php');
include_once(__DIR__.'/../utils/enviar-preco.php');
include_once(__DIR__.'/../services/kit-produtos/enviar-preco-kit.php'); // <--- INCLUÍDO NOVO ARQUIVO
include_once(__DIR__.'/../services/kit-produtos/enviar-saldo-kit.php'); // <--- INCLUÍDO NOVO ARQUIVO

$ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);
$appToken = $ini['conexao']['token'] ?? exit('Token não fornecido');

// Instancia objetos
$obj_env_saldo = new EnviarSaldo();
$obj_env_preco = new EnviarPreco();
$obj_env_preco_kit = new EnviarPrecoKit(); // <--- INSTANCIA CLASSE DE PREÇO KIT
$obj_env_saldo_kit = new EnviarSaldoKit(); // <--- INSTANCIA CLASSE DE PREÇO KIT

// Instancia conexões
$publico = new CONEXAOPUBLICO();
$estoque = new CONEXAOESTOQUE();
$vendas = new CONEXAOVENDAS();
$eventos = new CONEXAOEVENTOS();
$integracao = new CONEXAOINTEGRACAO();

$database_eventos = $eventos->getBase();
$database_integracao = $integracao->getBase();

try {
    // Busca os eventos pendentes
    $sql = "SELECT * FROM {$database_eventos}.eventos_produtos_sistema 
            WHERE status = 'PENDENTE' 
            ORDER BY id ASC LIMIT 100";
    
    $res_eventos = $eventos->Consulta($sql);
    $total_eventos = mysqli_num_rows($res_eventos);

    if ($total_eventos === 0) {
        exit(0); 
    }

    echo "Processando {$total_eventos} eventos...\n";

    $eventos_dados = [];
    $ids_produtos = [];
    
    while ($row = mysqli_fetch_array($res_eventos, MYSQLI_ASSOC)) {
        $eventos_dados[] = $row;
        $ids_produtos[] = intval($row['id_registro']); 
    }
    
    // Cache de Vínculos (Produtos Simples)
    $mapa_vinculos = [];
    if (!empty($ids_produtos)) {
        $ids_str = implode(',', $ids_produtos);
        $sql_bulk = "SELECT CODIGO_BD, codigo_site FROM produto_precode WHERE CODIGO_BD IN ($ids_str)";
        $res_bulk = $integracao->Consulta($sql_bulk);
        while($v = mysqli_fetch_assoc($res_bulk)){
            $mapa_vinculos[$v['CODIGO_BD']] = $v['codigo_site'];
        }
    }
    
    // Cache de Vínculos (Kits) - Descobre quais kits contêm esses produtos
    $mapa_kits_afetados = [];
    if (!empty($ids_produtos)) {
        $ids_str = implode(',', $ids_produtos);
        // Busca ID_KIT onde o produto faz parte
     //   $sql_kits = "SELECT DISTINCT ID_KIT, CODIGO_BD FROM itens_kit WHERE CODIGO_BD IN ($ids_str)";

        $sql_kits ="SELECT 
                DISTINCT pp.id AS ID_KIT,
                cp.CODIGO as  CODIGO_BD
            FROM ".$database_integracao.".padronizados as pp  
            join cad_padr cpd on cpd.CODIGO = pp.CODIGO_PADR
            join ite_padr ip  on ip.PADRONIZADO = pp.CODIGO_PADR
            join cad_prod cp on cp.CODIGO = ip.PROD_SERV
                	WHERE  ip.PROD_SERV  IN ($ids_str);";

        
        $res_kits = $publico->Consulta($sql_kits);
        while($k = mysqli_fetch_assoc($res_kits)){
            // Cria um array: Produto ID => [Lista de Kits ID]
            $mapa_kits_afetados[$k['CODIGO_BD']][] = $k['ID_KIT'];
        }
    }

    foreach ($eventos_dados as $row) {
        $id_evento = $row['id'];
        $codigo_produto = $row['id_registro'];
        $tabela_origem = $row['tabela_origem'];
        
        $possui_vinculo_simples = isset($mapa_vinculos[$codigo_produto]);
        $faz_parte_de_kit = isset($mapa_kits_afetados[$codigo_produto]);

        // 1. Processamento de Produtos Simples
        if ($possui_vinculo_simples) {
            if ($tabela_origem == 'pro_orca' || $tabela_origem == 'prod_setor') {
                echo "[ID $id_evento] Prod $codigo_produto: Enviando Saldo Simples...\n";
                $obj_env_saldo->postSaldo($codigo_produto, $publico, $estoque, $vendas, $integracao);
            }
            elseif ($tabela_origem == 'prod_tabprecos') {
                echo "[ID $id_evento] Prod $codigo_produto: Enviando Preço Simples...\n";
                $obj_env_preco->postPreco($codigo_produto, $publico, $integracao);
            }
        } 
        
        // 2. Processamento de KITS (Se o preço do componente mudou, o preço do kit muda)
        if ($faz_parte_de_kit && $tabela_origem == 'prod_tabprecos') {
            $lista_kits = $mapa_kits_afetados[$codigo_produto];
            foreach($lista_kits as $id_kit){
                echo "[ID $id_evento] Prod $codigo_produto altera Kit ID $id_kit: Atualizando Preço Kit...\n";
                // Chama a classe de envio de preço do kit
                $retornoKit = $obj_env_preco_kit->postPrecoKit($id_kit, $publico, $integracao);
                // Opcional: Logar retorno
                // echo "   -> " . $retornoKit . "\n";
            }
        }
          if ($faz_parte_de_kit && $tabela_origem == 'pro_orca' || $tabela_origem == 'prod_setor') {
            $lista_kits = $mapa_kits_afetados[$codigo_produto];
            foreach($lista_kits as $id_kit){
              echo "[ID $id_evento] Prod $codigo_produto altera Kit ID $id_kit: Atualizando estoque Kit...\n";
                // Chama a classe de envio de preço do kit
                 $retornoKit = $obj_env_saldo_kit->postSaldoKit($id_kit, $publico, $estoque, $vendas, $integracao);
              
                // Opcional: Logar retorno
                // echo "   -> " . $retornoKit . "\n";
            }
        }
        
        // Se não tem vínculo nenhum
        if (!$possui_vinculo_simples && !$faz_parte_de_kit) {
             echo "[ID $id_evento] Prod $codigo_produto: Ignorado (sem vínculo).\n";
        }

        // Atualiza status do evento
        $eventos->Consulta("UPDATE {$database_eventos}.eventos_produtos_sistema SET status = 'PROCESSADO' WHERE id = $id_evento");
    }

} catch (Exception $e) {
    echo "Erro PHP: " . $e->getMessage() . "\n";
    exit(1); 
}
?>