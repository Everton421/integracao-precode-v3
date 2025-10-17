<?php
header("Content-type: text/html; charset=utf-8");

class CONEXAOVENDAS {
    private string $consulta = "";
    public ?mysqli $link = null;
    private array $ini;
    private string $host;
    private string $login;
    private string $senha;
    private string $base_vendas;
    private int $porta;

    public function getBase(){
        return $this->base_vendas;
    }
    public function __construct() {
        $this->Conecta();
    }

    public function Conecta(): void {
        try {
            $this->ini = parse_ini_file(__DIR__ .'/../../conexao.ini', true);
            $this->host = $this->ini['conexao']['host'];
            $this->login = $this->ini['conexao']['login'];
            $this->senha = $this->ini['conexao']['senha'];
            $this->base_vendas = $this->ini['conexao']['banco_vendas'];
            $this->porta = $this->ini['conexao']['porta'];

            $this->link = new mysqli($this->host . ':' . $this->porta, $this->login, $this->senha, $this->base_vendas);

            if ($this->link->connect_error) {
                die('Connect Error (' . $this->link->connect_errno . ') ' . $this->link->connect_error);
            }
        } catch (\Exception $e) {
            // Trate exceções conforme necessário
        }
    }

    public function Desconecta(): bool {
        if ($this->link) {
            return $this->link->close();
        }
        return false;
    }

    public function Consulta(string $consulta): mixed {
        $this->consulta = $consulta;
        $resultado = $this->link->query($this->consulta);

        if ($resultado) {
            return $resultado;
        } else {
            return 0;
        }
    }
}
?>
