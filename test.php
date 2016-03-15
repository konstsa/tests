<?php
$temp_dir="/var/tmp/upload/";// директория для времяных файлов
$work_dir="/var/www/upload/";// рабочий какталог для хранения файлов

if(!isset($_POST['mode']) || ($_POST['mode']!='register' && $_POST['mode']!='list' && $_POST['mode']!='upload')){
	$out['err']="Not passed parameter 'mode', must contain one of the values ('register','list','upload')";
}else if($_POST['mode']=='register'){
	$z="ska:".time().mt_rand(1, 10000);
	$dirname = base64_encode($z);
    $out['id']=$dirname;
    $out['err']="";
	mkdir($z);
	$fp=fopen($work_dir."/".$z."/index.htm","a+");
	fwrite($fp,"<html><body></body></html>");
	fclose($fp);
}else if($_POST['mode']=='upload'){
	$err=false;
	if(!isset($_POST['id']) || trim($_POST['id'])==""){
		$out['err']="For mode 'upload' id must be transmitted";$err=true;
	};
	if(!isset($_POST['id']) || trim($_POST['id'])=="" || base64_decode($_POST['id'])==""){
		$out['err']="For mode 'upload' id must be transmitted";$err=true;
	};
	if(!isset($_POST['width']) || trim($_POST['width'])==""){
		$out['err']="For mode 'upload' width must be transmitted";$err=true;
	}else if(!ctype_digit($_POST['width'])){
		$out['err']="For mode 'upload' width must be consists only of digits";$err=true;
	};
	if(!isset($_POST['height']) || trim($_POST['height'])==""){
		$out['err']="For mode 'upload' height must be transmitted";$err=true;
	}else if(!ctype_digit($_POST['height'])){
		$out['err']="For mode 'upload' height must be consists only of digits";$err=true;
	};
	if($err===false){
		if(!isset($_FILES['file_contents'])){
			$out['err']="For mode 'upload' file_contents must be transmitted";
		}else{
			$ext = explode('.', $_FILES['file_contents']['name']);
			$ext = strtolower($ext[count($ext)-1]);
			$oldname=explode(".".$ext, $_FILES['file_contents']['name']);
			$oldname=$oldname[0];
			$filename = date("YmdHis");
			move_uploaded_file($_FILES['file_contents']['tmp_name'], $temp_dir.$filename.'.'.$ext);
			$info = getimagesize($temp_dir.$filename.'.'.$ext);
			$tmp_ext = str_replace('image/', '', $info['mime']);
			if ($ext != $tmp_ext) {
				rename($temp_dir.$filename.'.'.$ext, $temp_dir.$filename.'.'.$tmp_ext);
				$ext = $tmp_ext;
			};
			$path=$temp_dir.$filename.'.'.$ext;
			if ($ext != 'jpg' && $ext != 'jpeg' && $ext != 'gif' && $ext != 'png') {
				unlink($temp_dir.$filename.'.'.$ext);
			  	$out['err']="Is not supported the type of image";
			}else{
				$dir=$work_dir.base64_decode($_POST['id']);
				if(!is_dir($dir)){
			  		$out['err']="Is not valid id";
				}else{
					if ($ext == "png") $src = imagecreatefrompng($path);
					else if ($ext == "jpeg" || $ext == "jpg") $src = imagecreatefromjpeg($path);
					else if ($ext == "gif") $src = imagecreatefromgif($path);
					try{
						$thumb = imagecreatetruecolor($_POST['width'], $_POST['height']);
					}catch (Exception $e) {
						$out['err']="Can't create an image of this size";
						$err=true;
					}
					if($err===false){
						imagecopyresampled($thumb, $src, 0, 0, 0, 0, $_POST['width'], $_POST['height'], $info[0], $info[1]);
						$newname=$oldname."_".$filename."_w".$_POST['width']."_h".$_POST['height'].".".$ext;
						if ($ext == "png") imagepng($thumb, $dir."/".$newname);
						else if ($ext == "jpeg" || $ext == "jpg") imagejpeg($thumb, $dir."/".$newname);
						else if ($ext == "gif") imagegif($thumb, $dir."/".$newname);
						$url=str_replace($_SERVER["DOCUMENT_ROOT"],"",$dir);
						$url.="/".$newname;
						$image['url']="http://".$_SERVER["HTTP_HOST"].$url;
						$image['width']=$_POST['width'];
						$image['height']=$_POST['height'];
						$out['images'][]=$image;
					}
				}
			unlink($temp_dir.$filename.'.'.$ext);
			}
		}
	}
}else if($_POST['mode']=='list'){
	if(!isset($_POST['id']) || trim($_POST['id'])=="" || base64_decode($_POST['id'])==""){
		$out['err']="For mode 'list' id must be transmitted";
	}else{
		$dir=$work_dir.base64_decode($_POST['id']);
		if(!is_dir($dir)){
			$out['err']="Is not valid id";
		}else{
			$URL=str_replace($_SERVER["DOCUMENT_ROOT"],"",$dir);
			$files = scandir($dir);
		    foreach($files as $file) {
		      	if ($file=="." || $file==".." || $file=="index.htm") continue;
				$image['url']="http://".$_SERVER["HTTP_HOST"].$URL."/".$file;
				$e=explode("_w",$file);
				$e=$e[count($e)-1];
				$w=explode("_h",$e);
				$h=explode(".",$w[1]);
				$image['width']=$w[0];
				$image['height']=$h[0];
				$out['images'][]=$image;
		    }
		    if(!isset($out['images'])){
				$out['err']="You not have uploaded images";
		    }
		}
	}
}
if(!isset($out)){
	$out['err']="Is there a problem";
}
header('Content-Type: application/json');
echo json_encode($out);
?>
