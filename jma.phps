<?php
/**
 * 気象庁のwebページから最新のデータを取得し、整形して出力
 *
 * 気象庁のwebページから最新のデータを取得し、余分な文字列を削除して出力する<br />
 * 2015/04/11 ver1.0<br />
 *
 * @package usoinfo
 * @version 1.0
 */

	require_once 'HTTP/Request2.php';
    $use_url = "http://www.jma.go.jp/jp/amedas_h/today-#CODE#.html";
	define("JMA_URL", "http://www.jma.go.jp/jp/amedas_h/today-#CODE#.html");	// 取得するURL

	/**
	 * 標準の設定
	 */
	function JMA_config()
	{
		return array(
			'charset'	=> 'UTF-8',	// 文字コード
			'needle'	=> '時刻',	// オフセット検索文字列
			'line1'		=> '時刻',	// １行目判定
			'line2'		=> '時',	// ２行目判定
			'spec'		=> array(	// タイトルとデータのマップ
				'time'	=> '時刻',
				'temp'	=> '気温',
				'rain'	=> '降水量',
				'vane'	=> '風向',
				'wind'	=> '風速',
				'sun'	=> '日照時間',
				'snow'	=> '積雪深',
				'humi'	=> '湿度',
				'pres'	=> '気圧',
			),
		);
	}

	/*
	 * 気象庁のwebページからHTMLを取得する
	 * $code 取得する観測地点のコード 数字5桁
	 * $base 取得URLのベース
	 */
	function JMA_getPage($code, $base = JMA_URL)
	{
		if( !$code ) return false;
		$url	= str_replace('#CODE#', $code, $base);
		$http = new HTTP_Request2($url, HTTP_Request2::METHOD_GET);
		$result = $http->send();
		return $result->getBody();
	}

	/*
	 * HTMLからデータを抽出する
	 * $page 気象庁のwebページのHTMLデータ
	 * $conf 設定
	 *
	 * 返値
	 * $data['info'] 内容についての情報を含む配列
	 *  key データのキー(time/temp/rain...)
	 *  $data['info'][key]['title'] データのタイトル = １行目
	 *  $data['info'][key]['unit'] データの単位など = ２行目
	 * $data['data'] データの配列
	 *  $data['data'][n]['time'] 時
	 *  $data['data'][n]['temp'] 気温
	 *  $data['data'][n]['rain'] 降水量
	 *  $data['data'][n]['vane'] 風向
	 *  $data['data'][n]['wind'] 風速
	 *  $data['data'][n]['sun'] 日照時間
	 *  $data['data'][n]['snow'] 積雪深
	 *  $data['data'][n]['humi'] 湿度
	 *  $data['data'][n]['pres'] 気圧
	 */
	function JMA_parsePage($page, $conf)
	{
		$pos	= mb_strpos($page, $conf['needle'], 0, $conf['charset']);
		if( preg_match("/<table[\s\S]*?<\/table>/", $page, $match, 0, $pos) <= 0 ){
			return false;
		}
		
		$data	= array();
		$part	= $match[0];

		$from	= array('&nbsp;',"\t",'</tr>','>');
		$to		= array('','','</tr>*','> ');
		$part	= str_replace($from, $to, $part);
		$part	= strip_tags($part);
		$part	= preg_replace("/\s{2,}/"," ",$part);
		$list	= explode("*", $part);
		
		forEach($list as $l){
			$e	= explode(" ", trim($l));
			if( $e[0] == $conf['line1'] ){
				forEach($e as $ei => $ev){
					forEach($conf['spec'] as $vkey => $vtitle){
						if( $ev == $vtitle ){
							$set['keyset'][$vkey]['index']	= $ei;
							$set['keyset'][$vkey]['title']	= $ev;
							$set['idxset'][$ei]	= $vkey;
							break;
						}
					}
				}
				continue;
			}
			if( $e[0] == $conf['line2'] ){
				forEach($e as $ei => $ev){
					$set['keyset'][ $set['idxset'][$ei] ]['unit']	= $ev;
				}
				continue;
			}
			
			if( isset($e[1]) ){
				$day	= array();
				forEach($e as $ei => $ev){
					$day[ $set['idxset'][$ei] ]	= $ev;
				}
				$data['data'][]	= $day;
			}
		}
		
		forEach($set['idxset'] as $i => $v){
			$data['info'][$v]	= $set['keyset'][$v];
		}
		return $data;
	}
	
	/*
	 * 気象庁のwebページからデータを取得する
	 * $code 取得する観測地点のコード 数字5桁
	 */
	function JMA_get($code)
	{
		return JMA_parsePage(JMA_getPage($code), JMA_config());
	}
	
?>