<?php
//include_once('funcoes.php');
class CONEXAOESTOQUE  {
	var $consulta = "";
  	var $link = ""; 
    	var $ini;// = parse_ini_file('../../conexao.ini', true);
	var $url;// = $this->ini['conexao']['url'];
	var $login;// = $this->ini['conexao']['login'];
	var $senha;// = $this->ini['conexao']['senha'];
	var $base_estoque;// = $this->ini['conexao']['banco_publico'];
	var $porta;// = $this->ini['conexao']['porta']; 	

function CONEXAOESTOQUE(){
	
  	$this->Conecta();
}
function Conecta(){
	try {
		//$criptografa = new funcoes();
		$this->ini = parse_ini_file('conexao_recebe.ini', true);
		$this->url = $this->ini['conexao']['url'];
		$this->login = $this->ini['conexao']['login'];
		//$this->login = $criptografa->decripta($this->login);
		$this->senha = $this->ini['conexao']['senha'];
		//$this->senha = $criptografa->decripta($this->senha);
		$this->base_estoque = $this->ini['conexao']['banco_estoque'];
		$this->porta = $this->ini['conexao']['porta']; 
		$this->link = mysqli_init();
		if (!$this->link ) {
			die('mysqli_init failed');
		}
		mysqli_options($this->link,MYSQLI_OPT_CONNECT_TIMEOUT,0);
		$this->link = mysqli_connect($this->url.':'.$this->porta,$this->login,$this->senha, $this->base_estoque);
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