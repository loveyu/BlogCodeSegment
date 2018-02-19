<?php
/**
 * User: loveyu
 * Date: 2018/2/18
 * Time: 23:13
 */

namespace loveyu\Geo\Wbosm;


class ShpImport
{
	private $error_num = 0;
	private $success_num = 0;

	private $tbl_map = [];

	/**
	 * ShpImport constructor.
	 */
	public function __construct()
	{
	}

	public function import()
	{
		$dir_list = glob(GeoShp_DataDir."*");
		foreach($dir_list as $item) {
			if(!is_dir($item)) {
				continue;
			}
			$file_list = glob("{$item}/*.zip");
			foreach($file_list as $zip_file) {
				$ext = pathinfo($zip_file, PATHINFO_EXTENSION);
				if($ext != "zip") {
					continue;
				}
				if(!is_file($zip_file) || is_file($zip_file.".lock")) {
					continue;
				}
				$unzip_path = $this->process_file($zip_file);
				$this->unzip_import($zip_file, $unzip_path);
			}
		}
		echo "\nError Num:{$this->error_num}\n";
		echo "Success Num:{$this->success_num}\n";
	}

	private function unzip_import($zip_file, $unzip_path)
	{
		$shp_list = glob("{$unzip_path}/*.shp");
		if(empty($shp_list)) {
			system("rm -rf \"{$unzip_path}\"");
//			system("rm -rf \"{$zip_file}\"");
		}
//		return;
		foreach($shp_list as $item) {
			$name = pathinfo($item, PATHINFO_FILENAME);
			$tbl = explode("_", $name);
			array_pop($tbl);
			$name2 = implode("_", $tbl);
			if(preg_match("/^[a-zA-Z0-9_ ]+$/", $name2) < 1) {
				//rename
				$new_name = sha1($name2);
				foreach(glob("{$unzip_path}/{$name2}*") as $item2) {
					rename($item2, str_replace($name2, $new_name, $item2));
				}
				$item = str_replace($name2, $new_name, $item);
			}
//			if(!isset($this->tbl_map[$tbl])) {
//				echo $tbl;
//				$shp_exe = EXE_shp2pgsql;
//				$tbl_out_path = BCS_DATA."wbosm_tbl.{$tbl}.sql";
//				system("\"{$shp_exe}\" -I -s 4326 -p \"{$item}\" {$tbl} > {$tbl_out_path}");
//				$this->tbl_map[$tbl] = 1;
//			}
			$shp_exe = EXE_shp2pgsql;
			$tbl_out_path = $item.".sql";
			system("\"{$shp_exe}\" -s 4326 -a -e \"{$item}\" al_tbl > \"{$tbl_out_path}\"");
			$pgexe = EXE_psql;
			$res = system("\"{$pgexe}\" -U postgres -d wb_osm_db -f \"{$tbl_out_path}\" ");
//			if($res === "") {
//				continue;
//			}
//			exit;
		}
	}

	private function process_file($file)
	{
		static $inc_num = 0;
		$inc_num++;
		echo "{$inc_num}: {$file}\n";
		$unzip_path = "{$file}.unzip";
		if(is_dir($unzip_path)) {
//			system("rm -rf {$unzip_path}");
		} else {
			$p7zip = EXE_7zip;
			$res = system("\"{$p7zip}\" x \"{$file}\" -y -o\"{$unzip_path}\"");
			$info = explode(": ", $res);
			if(empty($info) || $info[0] != "Compressed" || $info[1] < 1) {
				unlink($file);
				$this->error_num++;
				return null;
			} else {
				$this->success_num++;
			}
		}

		return $unzip_path;
	}
}