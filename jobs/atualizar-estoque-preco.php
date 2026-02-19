<?php
// Configurações para script de longa execução
set_time_limit(0); 
ini_set('memory_limit', '256M');

include_once(__DIR__.'/../database/conexao_publico.php');
include_once(__DIR__.'/../database/conexao_estoque.php'); 
include_once(__DIR__.'/../database/conexao_vendas.php');
include_once(__DIR__.'/../database/conexao_integracao.php');
include_once(__DIR__.'/../database/conexao_eventos.php');
include_once(__DIR__.'/../utils/enviar-saldo.php');
include_once(__DIR__.'/../utils/enviar-preco.php');

$ini = parse_ini_file(__DIR__ .'/../conexao.ini', true);
$appToken = $ini['conexao']['token'] ?? exit('Token não fornecido');

$obj_env_saldo = new EnviarSaldo();
$obj_env_preco = new EnviarPreco();

$publico = new CONEXAOPUBLICO();
$estoque = new CONEXAOESTOQUE();
$vendas = new CONEXAOVENDAS();
$eventos = new CONEXAOEVENTOS();
$integracao = new CONEXAOINTEGRACAO();

$database_eventos = $eventos->getBase();

try {
    // Busca os eventos
    $sql = "SELECT * FROM {$database_eventos}.eventos_produtos_sistema 
            WHERE status = 'PENDENTE' 
            ORDER BY id ASC LIMIT 100";
    
    $res_eventos = $eventos->Consulta($sql);
    $total_eventos = mysqli_num_rows($res_eventos);

    if ($total_eventos === 0) {
        // Se o Node fez o trabalho certo, isso raramente vai acontecer.
        // Não use sleep() aqui, apenas saia para liberar o processo Node.
        exit(0); 
    }

    echo "Processando {$total_eventos} eventos...\n";

    // OTIMIZAÇÃO: Cache de produtos vinculados para evitar N+1 queries
    // Vamos coletar todos os IDs de produtos deste lote
    $eventos_dados = [];
    $ids_produtos = [];
    
    while ($row = mysqli_fetch_array($res_eventos, MYSQLI_ASSOC)) {
        $eventos_dados[] = $row;
        $ids_produtos[] = intval($row['id_registro']); // ID do produto
    }
    
    // Busca todos os vínculos de uma só vez (WHERE IN)
    $mapa_vinculos = [];
    if (!empty($ids_produtos)) {
        $ids_str = implode(',', $ids_produtos);
        $sql_bulk = "SELECT CODIGO_BD, codigo_site FROM produto_precode WHERE CODIGO_BD IN ($ids_str)";
        $res_bulk = $integracao->Consulta($sql_bulk);
        while($v = mysqli_fetch_assoc($res_bulk)){
            $mapa_vinculos[$v['CODIGO_BD']] = $v['codigo_site'];
        }
    }

    // Processa o loop agora sem fazer query extra de verificação
    foreach ($eventos_dados as $row) {
        $id_evento = $row['id'];
        $codigo_produto = $row['id_registro'];
        $tabela_origem = $row['tabela_origem'];
        
        // Verifica se existe no mapa carregado previamente
        $possui_vinculo = isset($mapa_vinculos[$codigo_produto]);

        if ($possui_vinculo) {
            if ($tabela_origem == 'pro_orca' || $tabela_origem == 'prod_setor') {
                echo "[ID $id_evento] Prod $codigo_produto: Enviando Saldo...\n";
                $obj_env_saldo->postSaldo($codigo_produto, $publico, $estoque, $vendas, $integracao);
            }
            elseif ($tabela_origem == 'prod_tabprecos') {
                echo "[ID $id_evento] Prod $codigo_produto: Enviando Preço...\n";
                $obj_env_preco->postPreco($codigo_produto, $publico, $integracao);
            }
        } else {
             echo "[ID $id_evento] Prod $codigo_produto: Ignorado (sem vínculo).\n";
        }

        // Atualiza status
        $eventos->Consulta("UPDATE {$database_eventos}.eventos_produtos_sistema SET status = 'PROCESSADO' WHERE id = $id_evento");
    }

} catch (Exception $e) {
    echo "Erro PHP: " . $e->getMessage() . "\n";
    exit(1); // Retorna erro para o Node saber
}
 
/*
    try {
        // Busca apenas um lote por vez (ex: 50) para não sobrecarregar
        $sql = "SELECT * FROM {$database_eventos}.eventos_produtos_sistema 
                WHERE status = 'PENDENTE' 
                ORDER BY id ASC LIMIT 100";
        
        $res_eventos = $eventos->Consulta($sql);

        if (mysqli_num_rows($res_eventos) === 0) {
            // Se não há eventos, espera um pouco mais para poupar CPU
             exit(0); 
        }

        while ($row = mysqli_fetch_array($res_eventos, MYSQLI_ASSOC)) {
            $id_evento = $row['id'];
            $codigo_produto = $row['id_registro'];
            $tabela_origem = $row['tabela_origem'];

            $processado = false;

            // LÓGICA DE ESTOQUE (pro_orca ou prod_setor)
            if ($tabela_origem == 'pro_orca' || $tabela_origem == 'prod_setor') {
                echo "[ID $id_evento] Produto $codigo_produto: Verificando estoque...\n";
                
                // Corrigido: era $$integracao
                $sql_vinculo = "SELECT codigo_site FROM produto_precode WHERE CODIGO_BD = " . intval($codigo_produto);
                $res_vinculo = $integracao->Consulta($sql_vinculo);

                if (mysqli_num_rows($res_vinculo) > 0) {
                    $obj_env_saldo->postSaldo($codigo_produto, $publico, $estoque, $vendas, $integracao);
                    echo " - Saldo enviado.\n";
                }
                $processado = true;
            }

            // LÓGICA DE PREÇO
            if ($tabela_origem == 'prod_tabprecos') {
                echo "[ID $id_evento] Produto $codigo_produto: Verificando preço...\n";
                
                $sql_vinculo = "SELECT codigo_site FROM produto_precode WHERE CODIGO_BD = " . intval($codigo_produto);
                $res_vinculo = $integracao->Consulta($sql_vinculo);

                if (mysqli_num_rows($res_vinculo) > 0) {
                   $resultEnvPrice = $obj_env_preco->postPreco($codigo_produto, $publico, $integracao);
                   echo " - Preço enviado.\n";
                }
                $processado = true;
            }

            // Atualiza o status para não processar novamente
            $novo_status =  'PROCESSADO'  ;
            $eventos->Consulta("UPDATE {$database_eventos}.eventos_produtos_sistema SET status = '$novo_status' WHERE id = $id_evento");
        }

        // Limpa memória do resultado
        mysqli_free_result($res_eventos);

    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage() . "\n";
        sleep(10); // Espera um pouco antes de tentar novamente após erro
    }
    */

 