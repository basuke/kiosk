<?php

class Kiosk_Utils_Path {
	/*
		渡されたパス要素を連結して返す。/の連続や ./././ などを除去する。 
	*/
	function concat($path) {
		$components = func_get_args();
		if (count($components) == 1) return $path;
		
		array_shift($components);
		$path .= '/'. join('/', $components);
		
		$path = preg_replace('|/+|', '/', $path);
		$path = preg_replace('|(\\./)+|', './', $path);
		
		return $path;
	}
	
	/*
		テンポラリな領域にディレクトリを作る
	*/
	
	function tempdir($dir, $prefix) {
		$tmp = tempnam($dir, $prefix);
		unlink($tmp);
		mkdir($tmp, 0777);
		return $tmp;
	}
	
	/*
		再帰的にディレクトリを削除する
		削除したファイルのパスを配列で返す
	*/
	function delete($path, $dry_run = false) {
		$result = array();
		if (is_dir($path)) {
			
			$files = array_merge(glob($path. '/*'), glob($path. '/.*'));
			
			foreach ($files as $file) {
				if (preg_match('/\\.+$/', $file)) continue;
				
				if (is_dir($file)) {
					$deleted = $this->delete($file, $dry_run);
					array_splice($result, count($result), 0, $deleted);
				} else {
					if ($dry_run == false) {
						unlink($file);
					}
					$result[] = $file;
				}
			}
			
			if ($dry_run == false) {
				rmdir($path);
			}
		} else {
			if ($dry_run == false) {
				unlink($path);
			}
		}
		$result[] = $path;
		
		return $result;
	}
	
	function mkdir($path, $mode=0777) {
		if (file_exists($path)) return false;
		
		$parent = dirname($path);
		if (!file_exists($parent)) {
			if (!$this->mkdir($parent, $mode)) return false;
		}
		
		return mkdir($path, $mode);
	}
	
	function touch($path) {
		if (!$this->mkdir(dirname($path))) return false;
		return touch($path);
	}
	
}

