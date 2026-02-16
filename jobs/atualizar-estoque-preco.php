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
        // Busca apenas um lote por vez (ex: 50) para não sobrecarregar
        $sql = "SELECT * FROM {$database_eventos}.eventos_produtos_sistema 
                WHERE status = 'PENDENTE' 
                ORDER BY id ASC LIMIT 50";
        
        $res_eventos = $eventos->Consulta($sql);

        if (mysqli_num_rows($res_eventos) === 0) {
            // Se não há eventos, espera um pouco mais para poupar CPU
            sleep(5);
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
                    $obj_env_preco->postPreco($codigo_produto, $publico, $integracao);
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

 