<?php

include_once(__DIR__.'/../utils/enviar-saldo.php');
include_once(__DIR__.'/../utils/enviar-preco.php');
include_once(__DIR__.'/../services/kit-produtos/enviar-preco-kit.php'); // <--- INCLUÍDO NOVO ARQUIVO
include_once(__DIR__.'/../services/kit-produtos/enviar-saldo-kit.php'); // <--- INCLUÍDO NOVO ARQUIVO


class EventoService {
  private $publico, $estoque, $vendas,   $integracao;
    private $obj_env_saldo, $obj_env_preco, $obj_env_preco_kit, $obj_env_saldo_kit;
    private   $database_integracao;

    public function __construct($publico, $estoque, $vendas,   $integracao) {
        $this->publico = $publico;
        $this->estoque = $estoque;
        $this->vendas = $vendas;
        $this->integracao = $integracao;

        $this->database_integracao = $integracao->getBase();

        $this->obj_env_saldo = new EnviarSaldo();
        $this->obj_env_preco = new EnviarPreco();
        $this->obj_env_preco_kit = new EnviarPrecoKit();
        $this->obj_env_saldo_kit = new EnviarSaldoKit();
    }

    public function processarMensagem($jsonPayload) {
        $data = json_decode($jsonPayload, true);
        
        if (!$data) {
            throw new Exception("JSON inválido recebido.");
        }

        if (isset($data['tabela_origem'])) {
            return $this->processarEventoProduto($data);
        }
        
    }

    private function processarEventoProduto($data) {
        $id_evento      = $data['id_evento'];
        $codigo_produto = $data['id_registro'];
        $tabela_origem  = $data['tabela_origem'];

        echo " [x] Processando Produto: $codigo_produto | Origem: $tabela_origem\n";

        //   Verifica Vínculo Simples
        $sql_vinculo = "SELECT codigo_site FROM produto_precode WHERE CODIGO_BD = " . intval($codigo_produto);
        $res_vinculo = $this->integracao->Consulta($sql_vinculo);
        $possui_vinculo_simples = mysqli_num_rows($res_vinculo) > 0;

        //  Verifica Kits
        $mapa_kits_afetados = [];
        $sql_kits = "SELECT DISTINCT pp.id AS ID_KIT 
                     FROM {$this->database_integracao}.padronizados as pp  
                     JOIN cad_padr cpd ON cpd.CODIGO = pp.CODIGO_PADR
                     JOIN ite_padr ip  ON ip.PADRONIZADO = pp.CODIGO_PADR
                     WHERE ip.PROD_SERV = " . intval($codigo_produto);
        
        $res_kits = $this->publico->Consulta($sql_kits);
        while($k = mysqli_fetch_assoc($res_kits)){
            $mapa_kits_afetados[] = $k['ID_KIT'];
        }

        //   Executa as ações
        if ($possui_vinculo_simples) {
            $this->executarAcaoSimples($tabela_origem, $codigo_produto);
        }

        if (!empty($mapa_kits_afetados)) {
            $this->executarAcaoKit($tabela_origem, $mapa_kits_afetados);
        }

        
        return true;
    }

    private function executarAcaoSimples($tabela, $codigo) {
        if ($tabela == 'pro_orca' || $tabela == 'prod_setor') {
            $this->obj_env_saldo->postSaldo($codigo, $this->publico, $this->estoque, $this->vendas, $this->integracao);
        } elseif ($tabela == 'prod_tabprecos') {
            $this->obj_env_preco->postPreco($codigo, $this->publico, $this->integracao);
        }
    }

    private function executarAcaoKit($tabela, $kits) {
        foreach($kits as $id_kit) {
            if ($tabela == 'prod_tabprecos') {
                $this->obj_env_preco_kit->postPrecoKit($id_kit, $this->publico, $this->integracao);
            }
            if ($tabela == 'pro_orca' || $tabela == 'prod_setor') {
                $this->obj_env_saldo_kit->postSaldoKit($id_kit, $this->publico, $this->estoque, $this->vendas, $this->integracao);
            }
        }
    }

        public function teste(){
            
        }
} 