<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
session_start();

function out($d,int $c=200){http_response_code($c);echo json_encode($d);exit;}
$cfgFile=__DIR__.'/config.php';
if(!is_file($cfgFile))out(['error'=>'Server not configured'],500);
$cfg=require $cfgFile;
try{$pdo=new PDO("mysql:host={$cfg['host']};dbname={$cfg['db']};charset=utf8mb4",$cfg['user'],$cfg['pass'],
 [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
catch(Throwable $e){out(['error'=>'Database error'],500);}

$action=$_GET['action']??'';
$isPost=$_SERVER['REQUEST_METHOD']==='POST';
$body=[];
if($isPost&&stripos($_SERVER['CONTENT_TYPE']??'','application/json')===0){
 $body=json_decode(file_get_contents('php://input'),true)??[];}
function admin(){if(empty($_SESSION['admin']))out(['error'=>'Admin login required'],401);}
function csrf(){ // token required for all admin writes
 $t=$_SERVER['HTTP_X_CSRF_TOKEN']??'';
 if(empty($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'],$t))out(['error'=>'Bad token'],403);}
function safeImg(string $u):bool{
 return (bool)preg_match('#^(default\.jpg|assets/image/image_sets/[A-Za-z0-9_.-]+\.(png|jpe?g))$#i',$u);}

try{
switch($action){
case 'list':
 out($pdo->query('SELECT p.id,p.title,p.image_url,(SELECT COUNT(*) FROM hotspots h WHERE h.puzzle_id=p.id) AS spots FROM puzzles p ORDER BY p.id')->fetchAll()+[]);
case 'get':
 $s=$pdo->prepare('SELECT id,title,image_url,version FROM puzzles WHERE id=?');$s->execute([(int)($_GET['id']??0)]);
 $p=$s->fetch();if(!$p)out(['error'=>'Not found'],404);
 $s=$pdo->prepare('SELECT name,x,y,r FROM hotspots WHERE puzzle_id=? ORDER BY sort_order,id');$s->execute([$p['id']]);
 $p['spots']=array_map(fn($h)=>['name'=>$h['name'],'x'=>(float)$h['x'],'y'=>(float)$h['y'],'r'=>(float)$h['r']],$s->fetchAll());
 out($p);
case 'status':
 out(['admin'=>!empty($_SESSION['admin']),'csrf'=>$_SESSION['csrf']??null]);
case 'login':
 if(!$isPost)out(['error'=>'POST only'],405);
 $pw=(string)($body['password']??'');
 $s=$pdo->prepare("SELECT v FROM settings WHERE k='admin_hash'");$s->execute();$h=$s->fetchColumn();
 if($h===false){ // first-time setup
  if(strlen($pw)<8)out(['error'=>'First use: choose a password of at least 8 characters'],400);
  $pdo->prepare("INSERT INTO settings (k,v) VALUES ('admin_hash',?)")->execute([password_hash($pw,PASSWORD_DEFAULT)]);
 }elseif(!password_verify($pw,$h)){sleep(1);out(['error'=>'Wrong password'],403);}
 session_regenerate_id(true);$_SESSION['admin']=true;$_SESSION['csrf']=bin2hex(random_bytes(16));
 out(['admin'=>true,'csrf'=>$_SESSION['csrf']]);
case 'logout':
 $_SESSION=[];session_destroy();out(['admin'=>false]);
case 'upload':
 if(!$isPost)out(['error'=>'POST only'],405);admin();csrf();
 $f=$_FILES['image']??null;
 if(!$f||$f['error']!==UPLOAD_ERR_OK)out(['error'=>'Upload failed'],400);
 if($f['size']>5*1024*1024)out(['error'=>'Max 5 MB'],400);
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
 $ext=['image/jpeg'=>'jpg','image/png'=>'png'][$mime]??null;
 if(!$ext||!@getimagesize($f['tmp_name']))out(['error'=>'Only png/jpg allowed'],400);
 $dir=dirname(__DIR__).'/assets/image/image_sets';
 if(!is_dir($dir)&&!mkdir($dir,0755,true))out(['error'=>'Cannot create folder'],500);
 $name=bin2hex(random_bytes(8)).'.'.$ext;
 if(!move_uploaded_file($f['tmp_name'],"$dir/$name"))out(['error'=>'Cannot save file'],500);
 out(['image_url'=>"assets/image/image_sets/$name"]);
case 'save':
 if(!$isPost)out(['error'=>'POST only'],405);admin();csrf();
 $title=trim((string)($body['title']??''));$url=(string)($body['image_url']??'');
 if($title===''||mb_strlen($title)>100)out(['error'=>'Invalid title'],400);
 if(!safeImg($url))out(['error'=>'Invalid image path'],400);
 $spots=$body['spots']??[];if(!is_array($spots)||count($spots)>100)out(['error'=>'Too many targets'],400);
 $clean=[];foreach($spots as $i=>$h){
  $n=trim((string)($h['name']??''));
  if(!is_numeric($h['x']??null)||!is_numeric($h['y']??null)||!is_numeric($h['r']??null))out(['error'=>'Bad coords'],400);
  $clean[]=[mb_substr($n===''?'Item':$n,0,60),max(0,min(100,(float)$h['x'])),max(0,min(100,(float)$h['y'])),max(2,min(30,(float)$h['r'])),$i];}
 $pdo->beginTransaction();
 $id=(int)($body['id']??0);
 if($id){
  $s=$pdo->prepare('SELECT version FROM puzzles WHERE id=? FOR UPDATE');$s->execute([$id]);$v=$s->fetchColumn();
  if($v===false){$pdo->rollBack();out(['error'=>'Not found'],404);}
  if((int)$v!==(int)($body['version']??0)){$pdo->rollBack();out(['error'=>'Puzzle was changed by someone else. Reload it.','conflict'=>true],409);}
  $pdo->prepare('UPDATE puzzles SET title=?,image_url=?,version=version+1 WHERE id=?')->execute([$title,$url,$id]);
  $pdo->prepare('DELETE FROM hotspots WHERE puzzle_id=?')->execute([$id]);
 }else{
  $pdo->prepare('INSERT INTO puzzles (title,image_url) VALUES (?,?)')->execute([$title,$url]);$id=(int)$pdo->lastInsertId();
 }
 $ins=$pdo->prepare('INSERT INTO hotspots (puzzle_id,name,x,y,r,sort_order) VALUES (?,?,?,?,?,?)');
 foreach($clean as $c)$ins->execute([$id,...$c]);
 $pdo->commit();
 $s=$pdo->prepare('SELECT version FROM puzzles WHERE id=?');$s->execute([$id]);
 out(['id'=>$id,'version'=>(int)$s->fetchColumn()]);
case 'delete':
 if(!$isPost)out(['error'=>'POST only'],405);admin();csrf();
 $pdo->prepare('DELETE FROM puzzles WHERE id=?')->execute([(int)($body['id']??0)]);out(['ok'=>true]);
default: out(['error'=>'Unknown action'],400);
}
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();out(['error'=>'Server error'],500);}
