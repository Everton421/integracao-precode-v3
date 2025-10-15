<?php
//include_once('funcoes.php');
class CONEXAO  {
	var $consulta = "";
  	var $link = ""; 
    var $ini;
	var $url;
	var $login;
	var $senha;
	var $base;
	var $porta;

	function CONEXAO(){
		
		
	}
	function Conecta(){
		try {
			//$criptografa = new funcoes();
			$this->url = $this->url;
			$this->login = $this->login;
			$this->senha = $this->senha;
			$this->base = $this->base;
			$this->porta = $this->porta; 
			$this->link = mysqli_init();
			if (!$this->link ) {
				die('mysqli_init failed');
			}
			mysqli_options($this->link,MYSQLI_OPT_CONNECT_TIMEOUT,0);
			$this->link = mysqli_connect($this->url, $this->login, $this->senha, $this->base, $this->porta);
			
			
			if (mysqli_connect_errno()) {
				printf("Connect failed: %s\n", mysqli_connect_error());
				exit();
			}
		} catch (\Exception $e) {
			
		}
	}
	function Desconecta(){
		return mysqli_close($this->link);
	}
	function Consulta($consulta){
		$this->consulta = $consulta;
		if ($resultado = mysqli_query($this->link, $this->consulta)){
			return $resultado;
		} else {
			return 0;

		}
	  }
}
?>
