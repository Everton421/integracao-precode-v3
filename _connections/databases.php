<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Database {
    private $link;
    private $config;

    public function __construct() {
        $this->config = parse_ini_file('conexao_recebe.ini', true);
        $this->link = mysqli_connect(
            $this->config['conexao']['url'] . ':' . $this->config['conexao']['porta'], 
            $this->config['conexao']['login'], 
            $this->config['conexao']['senha'], 
            $this->config['conexao']['banco_publico']
        );

        if (mysqli_connect_errno()) {
            throw new Exception(mysqli_connect_error());
        }
    }

    public function close() {
        mysqli_close($this->link);
    }

    public function query($query) {
        $result = mysqli_query($this->link, $query);

        if (!$result) {
            throw new Exception(mysqli_error($this->link));
        }

        return $result;
    }

    public function testConnection() {
        if (mysqli_ping($this->link)) {
            return "Conexão bem-sucedida!";
        } else {
            return "Erro na conexão: " . mysqli_error($this->link);
        }
    }
	
}

?>
