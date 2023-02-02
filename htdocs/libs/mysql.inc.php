<?php

function quote_data(&$v, $k) { $v = addcslashes($v, "'\\"); }

class mysql {

	private $db;
	public $started=false;
	public $connected=false;

	public function __construct() {
		global $dbhost,$dbuser,$dbpass,$dbdata;
		$this->db=mysqli_connect($dbhost,$dbuser,$dbpass);

		if(!$this->db) return;
		$this->connected=true;
		if(!mysqli_select_db($this->db, $dbdata))return;
		$this->started=true;
	}

	public $lastquery, $results, $errno, $error, $datas, $nrows;

	public function query($sql){
		global $dbpref;
		$this->datas=null;
		$this->nrows=null;
		$this->newid=null;
		$sql=str_replace('#__',$dbpref,$sql);
		$start_time=microtime(true);
		$this->msec=0;
		$this->results=mysqli_query($this->db, $sql);
		$this->msec=(microtime(true)-$start_time)*1000;

		$this->lastquery=$sql;
		if(!$this->results){
			$this->errno=mysqli_errno($this->db);
			$this->error=mysqli_error($this->db);
			if(isset($_SERVER['SHOWQ'])or isset($_GET['debug']))var_dump($this);
			return false;
		}
		if($this->results==true){
			$this->nrows=mysqli_affected_rows($this->db);
			$this->newid=mysqli_insert_id($this->db);
		}else{
			$this->nrows=mysqli_num_rows($this->results);
			$this->datas=array();
		}
		if(isset($_SERVER['SHOWQ'])or isset($_GET['debug']))var_dump($this);
		return true;
	}

	public function search($fields,$from,$where="",$order="",$limit=""){
	  global $dbdata;
	  $time=0;
	  $query="SELECT $fields \n FROM $from \n";
	  if($where)$query.="WHERE $where ";
	  $md5=md5($query);
	  $memtab="#__cache_$md5";
	  $istab=$this->query("SHOW TABLES WHERE Tables_in_$dbdata='$memtab'");
	  if(!$this->nrows) {
	    $this->query("CREATE TABLE $memtab ENGINE=MyISAM $query ORDER BY rank DESC LIMIT 0,3000;");
	    $time=$this->msec;
	  }
	  $sql="SELECT * FROM $memtab ";
	  if($order)$sql.=" ORDER BY $order ";
	  if($limit)$sql.=" LIMIT $limit ";
	  $q=$this->query($sql);
	  if($time)$this->msec=$time;
	  return $q;
	}

	public function dropcache(){
	  global $dbdata;
	  $ldb=new self();
	  $istab=$this->query("SHOW TABLES WHERE Tables_in_$dbdata like '#__cache_%'");
	  while($x=$this->get())$ldb->query("DROP TABLE {$x['Tables_in_'.$dbdata]}");
	}

	public function fetchtable(){
	  while($this->fetch());
	}

	public function seek($seek){
	  return mysqli_data_seek($this->results,$seek);
	}

	public function fetch(){
	  if($tmp=mysqli_fetch_assoc($this->results)){
	    $this->datas[]=$tmp;
	    return count($this->datas)-1;
	  }
	  return false;
	}

	public function get(){
	  $tmp=$this->fetch();
	  if($tmp===false)return false;
	  return $this->datas[$tmp];
	}

	public function update($table,$data,$cond){
	  $sql="UPDATE {$table} ";
	  $sep="SET ";
	  foreach($data as $key => $value){
	    $sql.=$sep."{$key} = '{$value}' ";
	    $sep=", ";
	  }
	  $sep="WHERE ";
	  foreach($cond as $key => $value){
	    $sql.=$sep."{$key} = '{$value}' ";
	    $sep.="AND ";
	  }
	  return $this->query($sql);
	}

	private $insertdata=false;
	private $insertstart=false;
	public $maxdata=100000;

	public function insert($first=false,$second=false,$large=false){
	  /*
	   * se large=false, $first la tabella e $second sono i dati (in array
	   * associativo) viene fatta una query standard. Eventuali buffer
	   * vengono flushati e chiusi.
	   *
	   * se large=true, allora si sta inizializzando una query multipla in cui
	   * $first è la tabella è $second è un array dei campi
	   *
	   * le richieste successive avranno $large=false.
	   * Se in queste $first=false (cioè lanciato senza parametri) si forza un
	   * flush dei dati.
	   * Se in queste $first è un array allora rappresenta i dati e vengono
	   * accodati al buffer; se il buffer è pieno questo viene prima flushato.
	   */
	  if($first===false){ // richiesta di flush
	    if(!$this->insertstart)return false; // se non c'è un buffer allora fallisce
	    if(!$this->insertdata)return true; // l'inserimento di un buffer vuoto va a buon fine
	    $out=$this->query($this->insertstart.$this->insertdata); // inserisci e
	    $this->insertdata=false;             // svuota il buffer
	    if(!$out){
	      echo "\n\n";
	      var_dump($this);
	      die( "\n\nERRORE!!\n");
	    }
	    return $out;
	  }
	  if(is_array($second) and !$large){
	    if($this->insertstart)$this->insert(); $this->insertstart=false; // flusha eventuali buffer
	    // effettua query standard
	    if(isset($second[0])){
	      $sql="insert into #__$first (".implode(",",array_keys($second[0])).") values ";
	      $values=$second;
	    }else{
	      $sql="insert into #__$first (".implode(",",array_keys($second)).") value ";
	      $values=array(0 => $second);
	    }
	    array_walk_recursive($values,'quote_data');


	    foreach ($values as $key => $val)$values[$key]="'".implode("','",$val)."'";
	    $sql.="(".implode("),(",$values).")";
	    return $this->query($sql);
	  }
	  if(is_array($second) and $large){ // inizializza il buffer
	    if($this->insertdata)$this->insert();
	    $this->insertstart="insert into #__$first (".implode(",",$second).") values ";
	    return;
	  }
	  if(is_array($first)){
	    array_walk_recursive($first,'quote_data');
	    $data="('".implode("','",$first)."')";
	    if(!$this->insertdata)$this->insertdata="";
	    if(strlen($this->insertdata)+strlen($data) > $this->maxdata)$this->insert();
	    if($this->insertdata)$this->insertdata.=",";
	    $this->insertdata.=$data;
	    return;
	  }
	}

	public function close(){
	  return mysqli_close($this->db);
	}
}
