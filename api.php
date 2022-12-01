<?php
class RecordingSearch {
	private static $db = null;

	public static $user_id;
	public static $extension;

	private static function db(){
		if (empty(self::$db)){
			self::$db = Common::DB('cdr');
		}
		return self::$db;
	}

	private static function isValidID($cdr_id){
		return preg_match('/^[0-9]{4,5}-[0-9]{13}$/', $cdr_id);
	}

	public static function error($err_msg){
		header('Content-type: application/json; charset=utf-8');
		echo json_encode([ 'error' => $err_msg ]);
		exit();
	}

	public static function handleRequest(){
		extract($_REQUEST, EXTR_PREFIX_ALL, 'r');

		if (!empty($r_recent)){
			return self::search(NULL);
		}

		if (!empty($r_search) && preg_match('/^[0-9]{4,}$/', $r_search)){
			return self::search($r_search);
		}

		if (!empty($r_download) && self::isValidID($r_download)){
			return self::download($r_download);
		}

		if (!empty($r_listen) && self::isValidID($r_listen)){
			return self::listen($r_listen);
		}

		self::error('Invalid request');
	}

	private static function search($phone){
		$dateFrom = $_REQUEST['datefrom'];
		$dateTo = $_REQUEST['dateto'];
		$status = $_REQUEST['status'];
		$extension = self::$extension;
		$site_id = DIALER_SITE_ID;




		if ($phone === NULL){
			$res = self::db()->query(
				"SELECT *, UNIX_TIMESTAMP(calldate) AS calldate
					FROM cdr
					INNER JOIN recordings USING (cdr_id, site_id)
					INNER JOIN users_info u ON u.ps_endpoint_id = cdr.ps_endpoint_id
					WHERE cdr.ps_endpoint_id = '{$extension}' AND disposition = 'ANSWERED' AND site_id = '{$site_id}'
					ORDER BY calldate DESC
					LIMIT 5"
			);
		} else if (FULL_SEARCH){
			

				

			if (strlen($phone) == 4){

				if($status == 'check'){
					$clause = "cdr.ps_endpoint_id = '{$phone}' AND calldate BETWEEN convert_tz('".$dateFrom." 00:00:00', '+00:00', '-08:00') AND  convert_tz('".$dateTo." 23:59:59', '+00:00', '-08:00') ";
				}else{
					$clause = "cdr.ps_endpoint_id = '{$phone}' AND calldate >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
				}
			 
  
			}else{

				if($status == 'check'){
					$clause = "phone LIKE '%{$phone}%' AND calldate BETWEEN convert_tz('".$dateFrom." 00:00:00', '+00:00', '-08:00') AND  convert_tz('".$dateTo." 23:59:59', '+00:00', '-08:00') ";
				}else{
					$clause = "phone LIKE '%{$phone}%'";
				}

			}

			$res = self::db()->query(
				"SELECT *, UNIX_TIMESTAMP(calldate) AS calldate
					FROM cdr
					INNER JOIN recordings USING (cdr_id, site_id)
					INNER JOIN users_info u ON u.ps_endpoint_id = cdr.ps_endpoint_id
					WHERE {$clause} AND disposition = 'ANSWERED' AND site_id = '{$site_id}'
					ORDER BY calldate DESC"
			);
		} else {
			$res = self::db()->query(
				"SELECT *, UNIX_TIMESTAMP(calldate) AS calldate
					FROM cdr
					INNER JOIN recordings USING (cdr_id, site_id)
					INNER JOIN users_info u ON u.ps_endpoint_id = cdr.ps_endpoint_id
					WHERE phone LIKE '%{$phone}%' AND cdr.ps_endpoint_id = '{$extension}' AND disposition = 'ANSWERED' AND site_id = '{$site_id}'
					ORDER BY calldate DESC"
			);
		}

		if (self::db()->error){
			exit(self::db()->error);
		}

		$rec_files = [];

		while ($row = $res->fetch_object()){
			$name = $row->name;
			if (FULL_SEARCH){
				$name .= " (×$row->ps_endpoint_id)";
			}
			$rec_files[] = [ $row->cdr_id, $row->phone, $name, $row->calldate, $row->billsec, "{$row->ps_endpoint_id}-{$row->phone}-" . date('Ymd_His', $row->calldate) ];
		}

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($rec_files);
		exit();
	}

	private static function download($cdr_id){
		$res = self::db()->query(
			"SELECT *
			 FROM recordings
			 INNER JOIN cdr USING (cdr_id)
			 WHERE cdr_id = '{$cdr_id}'"
		);

		if ($row = $res->fetch_object()){
			$dt = date('Ymd_His', strtotime($row->calldate));
			$file = "{$row->phone}-{$dt}.WAV";

			header('Content-type: application/octet-stream');
			header('Content-disposition: attachment; filename="' . $file . '"');

			echo $row->recording;
			exit();
		} else {
			self::error("Recording not found.");
		}
	}

	private static function listen($cdr_id){
		$range_from = 0;
		$range_to = null;

		if (empty($_SESSION['listen'. $cdr_id])){

			$res = self::db()->query("SELECT recording FROM recordings WHERE cdr_id = '{$cdr_id}'");
			if ($res->fetch_row()){
				ob_start();
				passthru("sox \"|./raw-rec-out.php {$cdr_id}\" -t vorbis -", $sox_return);
				$audio = ob_get_clean();

				if ($sox_return > 0){
					header($_SERVER['SERVER_PROTOCOL'] . ' 415 Unsupported Media Type');
					echo 'Unsupported Media Type';
					exit();
				}

				$_SESSION['listen'. $cdr_id] = $audio;

			} else {
				header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
				echo "Not Found";
				exit();
			}
		} else {
			$audio = $_SESSION['listen'. $cdr_id];
		}

		if ($_SERVER['HTTP_RANGE']){
			[ , $bytes ] = explode('bytes=', $_SERVER['HTTP_RANGE']);
			[ $range_from, $range_to ] = explode('-', $bytes);
		}

		$length = strlen($audio);
		$audio = substr($audio, $range_from, $length);

		$content_length = strlen($audio);

		if (empty($range_to)){
			$range_to = $length - 1;
		}

		header($_SERVER['SERVER_PROTOCOL'] . ' 206 Partial Content');
		header('Accept-Ranges: bytes');
		header("Content-Type: audio/ogg");
		header("Content-Range: bytes {$range_from}-{$range_to}/{$length}");
		header("Content-Length: {$content_length}");
		echo $audio;
		exit();
	}
}

require 'config.php';

if (!Login::isLoggedIn()){
	RecordingSearch::error('Not logged in.');
}

RecordingSearch::$user_id = Login::info('userID');
RecordingSearch::$extension = Login::info('extension');

RecordingSearch::handleRequest();
