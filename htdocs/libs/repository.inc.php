<?php

class repository {

  public $db;
  public $pkgsfile=null;

  public function __construct($repo=0){
    $this->id=0;
    $this->db=new mysql();
    if($repo)$this->select($repo);
  }

  public function add($repo){
    foreach($repo as $key => $value) $this->$key = $value;
    //if(!$this->pkgsfile) $this->pkgsfile=new internet(((((substr($this->packages,0,7))=="http://")or((substr($this->packages,0,6))=="ftp://"))?"":$this->url).$this->packages);
    //if(!$this->pkgsfile->exists())return false;
    $repo['mtime']="n/a";
    if(!$this->db->insert('repository',$repo))return false;
    return $repo['id'];
  }

  public function update(){	# TO BE FIXED
    $this->pkgsfile=new internet(((((substr($this->packages,0,7))=="http://")or((substr($this->packages,0,6))=="ftp://"))?"":$this->url).$this->packages);
    $this->hash=$hashfile->download();
    if(!$this->pkgsfile->exists())return false;
    $this->mtime=$this->pkgsfile->head['Last-Modified'];
    $repo['mtime']=$this->mtime;
    if(!$this->db->query("update #__repository set mtime='".$this->mtime."' where id=".$this->id))return false;
    return true;
  }

  public function redefine($repo,$nf=''){
    $this->db->query("select count(*) as npkgs from #__packages where repository='{$repo['id']}'");
    $this->db->fetch();
    $repo['npkgs']=$this->db->datas[0]['npkgs'];
    if($nf){
      $this->db->query("select count(*) as nfiles from #__filelist where repository='{$repo['id']}'");
      $this->db->fetch();
      $repo['nfiles']=$this->db->datas[0]['nfiles'];
    }
    $top=$repo['rank'];
    $p1=$top* (pow($repo['npkgs'],1/4)/pow(2356,1/4)) *30/100;
    $p2=0; $p3=0; $p4=0;
    if ( isset($repo['deps']) )if($repo['deps']==1)$p2=$top*2/100;
    if ( $nf )$p3=$top*4/100;
    switch ($repo['version']){
      case '15.0': $p4=$top*20/100;break;
      case 'current': $p4=$top*17/100;break;
      case 'mixed': $p4=$top*15/100;break;
      case '14.2': $p4=$top*12/100;break;
      case '14.1': $p4=$top*10/100;break;
      case '14.0': $p4=$top*0/100;break;
    }
    $repo['rank']=round($top-($p1+$p2+$p3+$p4));
    $out=$this->db->update("#__repository",$repo,array("id" => "{$repo['id']}"));
    return ! ! $out;
  }

  public function popolate($more=array()){
    $allpackage=array();
    $pack=new package();
    if(!$this->pkgsfile)$this->pkgsfile=new internet(((((substr($this->packages,0,7))=="http://")or((substr($this->packages,0,6))=="ftp://"))?"":$this->url).$this->packages);
    if(!$this->pkgsfile->exists())return false;
    $i=0;
    while(!is_null($pkg=$pack->fetch($this->pkgsfile))){
      if($pkg){
	$id=$pack->add($pkg,array('repository'=>$this->id),$more);
	if(!$id){ return false; }
	if(isset($_SERVER['DEBUG']))echo $id." -> ".$pack->filename."               \n";
	if(isset($_SERVER["_"])){ echo "\r(packages $i: ".$pkg['name']."               " ; }else{ echo "."; }
	$allpackage[$pack->filename]=$id;
	$i++;
      }
    }
    $this->pkgsfile->close();
    if(!$this->db->query("update #__repository set npkgs='$i' where id='{$this->id}'"))var_dump($this->db);
    if(isset($_SERVER["_"])){ echo "\r(packages: $i                                            \n" ; }else{ echo "\n [$i packages] \n";}
    $list=new filelist();
    if($this->manifest){
      $this->manifile=new internet($this->url.$this->manifest);
      if(!$i=$list->addall($allpackage,$this)){var_dump($i);return false;}
      if(!$this->db->query("update #__repository set nfiles='$i' where id='{$this->id}'"))var_dump($this->db);
    }
    if(!$this->pkgsfile->exists())return false;
    $this->mtime=$this->pkgsfile->head['Last-Modified'];
    $repo['mtime']=$this->mtime;
    if(!$this->db->query("update #__repository set mtime='".$this->mtime."' where id=".$this->id))return false;
    return true;
  }

  public function needupdate(){
    if(!$this->pkgsfile)$this->pkgsfile=new internet(((((substr($this->packages,0,7))=="http://")or((substr($this->packages,0,6))=="ftp://"))?"":$this->url).$this->packages);
    if(!$this->pkgsfile->exists())return false;
    $mtime=$this->pkgsfile->head['Last-Modified'];
    return $mtime != $this->mtime;
  }

  public function drop(){
    $this->db->query("delete from #__filelist where repository=".$this->id);
    $this->db->query("delete from #__packages where repository=".$this->id);
    $err= $this->db->query("delete from #__repository where id=".$this->id);
    return $err;
  }

  public function download(){
  //  global $repodir;
  //  $path=$repodir.$this->path;
  //  if(!file_exists($path))if(!mkdir($path))return false;
  }

  public function select($repo){
    $this->id=0;
    if(!$this->db->query("select * from #__repository where id='$repo' or name='$repo'")) return false;
    if(!$repo=$this->db->get())return false;
    foreach($repo as $key => $value) $this->$key = $value;
  }

  public function find($repo=null){
    $sql="select * from #__repository ";
    if($repo)$sql.="where id='$repo' or name='$repo'";
    $this->db->query("$sql order by name");
    return $this->db->nrows;
  }

  public function fetch(){
    $this->id=0;
    if(!$out=$this->db->get())return false;
    foreach($out as $key => $value) $this->$key = $value;
    return $this->id;
  }
  public function exists(){
    return $this->id != 0;
  }

  static public function sql(){
    return "
      CREATE TABLE #__repository (
	id INT ,
	url VARCHAR( 255 ) NOT NULL ,
	rank INT DEFAULT '99',
	manifest VARCHAR( 30 ) ,
	packages VARCHAR( 30 ) ,
	version VARCHAR( 16 ) ,
	arch VARCHAR( 10 ) ,
	class VARCHAR( 10 ) ,
	mtime VARCHAR( 40 ),
	name VARCHAR( 40 ) ,
	npkgs INT DEFAULT '0',
	nfiles INT DEFAULT '0' ,
	deps INT DEFAULT '0' ,
	description VARCHAR( 255 ) ,
	brief VARCHAR( 50 ) ,
	PRIMARY KEY ( id ) ,
	UNIQUE ( name )
      ) ENGINE = MyISAM ;
    ";
  }
}
