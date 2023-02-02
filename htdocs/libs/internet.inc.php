<?php

class internet {
  public function __construct($url,$tmp=null){
    global $repodir;
    $this->url=$url;
    $this->parse();
    if($tmp){
      $this->tmpfile=$tmp;
    }else{
      $this->tmpfile="$repodir/{$this->file}.".getmypid();
      if(!$this->mode)$this->tmpfile.=$this->ext;
    }
  }
 
  public function parse(){
    $tmp=preg_split('#^(([^:]*)://)?([^/]*)($|/((.*)/)?(((([^/]*)(\.[^\.]*))|([^/]*)))?)$#i', $this->url,0,PREG_SPLIT_DELIM_CAPTURE);
    $this->proto=($tmp[2])?($tmp[2]):'file';
    $this->domain=$tmp[3];
    $this->fullpath=$tmp[4];
    $this->path="/".$tmp[6];
    $this->file=($tmp[10])?($tmp[10]):($tmp[12]);
    $this->ext=$tmp[11];
    switch(strtolower($this->ext)){
      case '.gz': $this->mode='z'; break;
      case '.xz': $this->mode='xz'; break;
      case '.bz2': $this->mode='bz'; break;
      default: $this->mode=''; break;
    }
  }

  var $head=null;

  public function gethead(){
    $this->getheadfopen();
    //$this->getheadcurl();
  }

  public function getheadfopen(){
    $head=array();$code=200;
    echo '{gethead '.$this->url.'}';
    $page=file_get_contents( $this->url,false,null,0 );
    $head=$http_response_header;
    $this->http_response_header=$head;
    if(is_array($head)){
      foreach($head as $val){
	$tmp=preg_split('/^([^ :]*):? (.*)?$/',$val,0,PREG_SPLIT_DELIM_CAPTURE);
	if(isset($tmp[2])) $this->head[$tmp[1]]=$tmp[2];
      }
      if(!isset($this->head['Last-Modified'])){
	if(isset($this->head['Content-Length'])){
	  $this->head['Last-Modified']="Date Unknown (filesize: ".$this->head['Content-Length'].")";
	}elseif(isset($this->head[213])){
	  $this->head['Last-Modified']="Date Unknown (filesize: ".$this->head[213].")";
	}
      }
      if(isset($this->head['HTTP/1.0'])){
	$this->head['HTTP/1.1']=$this->head['HTTP/1.0'];
      }
      if(isset($this->head['HTTP/1.1'])){
	$code=substr($this->head['HTTP/1.1'],0,3);
      }
    }
    if($code==200)$code=0;
    $this->status=$code;
    return $this->head;
  }

  public function getheadcurl(){
    $head=array();$code=0;
    exec("curl -I {$this->url} 2>/dev/null",$head,$code);
    foreach($head as $val){
      $tmp=preg_split('/^([^ :]*):? (.*)?$/',$val,0,PREG_SPLIT_DELIM_CAPTURE);
      if(isset($tmp[2])) $this->head[$tmp[1]]=$tmp[2];
    }
    if(!isset($this->head['Last-Modified']))$this->head['Last-Modified']="Date Unknown (filesize: ".$this->head['Content-Length'].")";
    if(isset($this->head['HTTP/1.1'])){
      $code=substr($this->head['HTTP/1.1'],0,3);
      if($code==200)$code=0;
    }
    $this->status=$code;
    return $this->head;
  }

  var $status=null;

  public function exists(){
    if (!$this->head) $this->gethead();
    return !$this->status;
  }

  var $fd;
  var $down;
  public function close(){
    if($this->fd)fclose($this->fd);
    $this->fd=null;
    if(file_exists($this->tmpfile)and !isset($_SERVER['DEBUG'])and !isset($_GET['DEBUG']))unlink($this->tmpfile);
  }
  public function __destruct(){
    $this->close();
    if(file_exists($this->tmpfile)and !isset($_SERVER['DEBUG'])and !isset($_GET['DEBUG']))unlink($this->tmpfile);
  }

  public function open(){
    if($this->fd)return $this->fd;
    if(!$this->down)$this->download();
    if(!$this->down)return false;
    $this->fd=fopen($this->tmpfile,'r'); 
    return $this->fd;
  }

  public function download($lines=0){
    /*
    $cmd="wget ";
    if(isset($_SERVER['WGET'])){$cmd.=$_SERVER['WGET']." ";}
    $cmd.="-O - ";
    $cmd.=$this->url;
    $cmd.="|{$this->mode}cat";
    if($lines)$cmd.="|head -$lines";
    $cmd.=" > {$this->tmpfile}";
    system($cmd,$err);
    $this->down=!$err;
    return $err;
     */
    if(isset($_SERVER['WGET'])){ $opt=$_SERVER['WGET']; }else{ $opt=""; }
    $sz=$this->wget($this->url,$this->tmpfile,$opt);
    $this->down=$sz;
    return !$sz;
  }
  public function wget($url,$dest,$opt=""){
    if(!$this->mode){
      $src=fopen($url,"r");
      $dst=fopen($dest,'w');
      $size=0;
      echo "(download $url";
      while(!feof($src)){
	$size+=fwrite($dst,fread($src,4096));
	if(isset($_SERVER["_"])){ echo "\r(download $url -> $size\r"; }else{ echo "."; }
      }
      if(isset($_SERVER["_"])){ echo "\r(download $url -> $dest($size bytes).\n"; }else{ echo "$dest($size bytes).\n"; }
      fclose($src);
      fclose($dst);
    }
    if($this->mode=="z"){
      $src=gzopen($url,"r");
      $dst=fopen($dest,'w');
      $size=0;
      echo "(download $url";
      while(!feof($src)){
	$size+=fwrite($dst,gzread($src,4096));
	if(isset($_SERVER["_"])){ echo "\r(download $url -> $size\r"; }else{ echo "."; }
      }
      echo "$dest($size bytes).\n";
      if(isset($_SERVER["_"])){ echo "\r(download $url -> $dest($size bytes).\n"; }else{ echo "$dest($size bytes).\n"; }
      fclose($src);
      fclose($dst);
    }
    if($this->mode=="bz"){

      $src=fopen($url,"r");
      $dst=fopen("$dest.bz2",'w');
      $size=0;
      echo "(download $url";
      while(!feof($src)){
	$size+=fwrite($dst,fread($src,4096));
	if(isset($_SERVER["_"])){ echo "\r(download $url -> $size\r"; }else{ echo "."; }
      }
      if(isset($_SERVER["_"])){ echo "\r(download $url -> $dest.bz2($size bytes).\n"; }else{ echo "$dest.bz2($size bytes).\n"; }
      fclose($src);
      fclose($dst);
      $src=bzopen("$dest.bz2","r");
      $dst=fopen($dest,'w');
      $size=0;
      echo "(uncompress $dest.bz2";
      while(!feof($src)){
	$size+=fwrite($dst,bzread($src,4096));
	if(isset($_SERVER["_"])){ echo "\r(uncompress $dest.bz2 -> $size\r"; }else{ echo "."; }
      }
      if(isset($_SERVER["_"])){ echo "\r(uncompress $dest.bz2 -> $dest($size bytes).\n"; }else{ echo "$dest($size bytes).\n"; }
      fclose($src);
      fclose($dst);
      if(!isset($_SERVER['DEBUG']) and !isset($_GET['DEBUG'])) unlink("$dest.bz2");
    }
    return $size;
  }

  public function get(){
    if (!$this->open())return false;
    if(feof($this->fd)){
      return null;
    }else{
      $f=fgets($this->fd);
      if(isset($_SERVER['DEBUG'])and $_SERVER['DEBUG']==2)echo "$f";
      if($f===false)return false;
      $t=trim($f,"\n");
      return $t;
    }
  }

  public function eof(){
    if (!$this->open())return false;
    return feof($this->fd);
  }
}
