<?php
namespace KAI_WU;
class SynoNas {
	protected $host;
	protected $port;
	protected $sid = NULL;
	protected $apiData;
	public $FileStation;
	function __construct(string $host, int $port) {
		$this->host = $host;
		$this->port = $port;
		$this->apiData = json_decode(file_get_contents("http://{$host}:{$port}/webapi/query.cgi?api=SYNO.API.Info&version=1&method=query&query=all"), true);
	}
	function __destruct() {
		if ($this->sid) {
			$this->logout();
		}
	}
	protected function httpClient(array $config) {
		$sender = curl_init();
		$url = "http://{$this->host}:{$this->port}/webapi/{$this->apiData['data'][$config['api']]['path']}?";
		if (!array_key_exists('version', $config))
			$config['version'] = $this->apiData['data'][$config['api']]['maxVersion'];
		if (isset($this->sid))
			$config['_sid'] = $this->sid;
		curl_setopt($sender, CURLOPT_URL, $url . http_build_query($config));
		curl_setopt($sender, CURLOPT_RETURNTRANSFER, true);
		$state = json_decode(curl_exec($sender), true);
		curl_close($sender);
		return $state;
	}
	public function login(string $user, string $pass, string $session) {
		$config = array(
			'api' => 'SYNO.API.Auth',
			'method' => 'login',
			'account' => $user,
			'passwd' => $pass,
			'session' => $session,
			'format' => 'cookie'
		);
		$state = $this->httpClient($config);
		if ($state['success']) {
			$this->sid = $state['data']['sid'];
			$this->FileStation = new FileStation($this->host, $this->port, $this->sid);
		}
	}
	public function logout() {
		$config = array(
			'api' => 'SYNO.API.Auth',
			'method' => 'logout',
		);
		$state = $this->httpClient($config);
		$this->sid = NULL;
	}
}
class FileStation extends SynoNas {
	function __construct($host, $port, $sid) {
		echo $this->host;
		parent::__construct($host, $port);
		$this->sid = $sid;
	}
	public function move(string $source, string $destination, array $settings = []) {
		return $this->httpClient(array_merge(array(
			'api' => 'SYNO.FileStation.CopyMove',
			'method' => 'start',
			'path' => $source,
			'dest_folder_path' => $destination,
			'overwrite' => 'false',
			'remove_src' => 'true'
		), $settings));
	}
	public function createFolder(string $path, string $name) {
		return $this->httpClient(array(
			'api' => 'SYNO.FileStation.CreateFolder',
			'method' => 'create',
			'folder_path' => $path,
			'name' => $name
		));
	}
	public function getInfo() {
		return $this->httpClient($config = array(
			'api' => 'SYNO.FileStation.Info',
			'method' => 'get'
		));
	}
	public function lsShared(array $settings = []) {
		return $this->httpClient(array_merge(array(
			'api' => 'SYNO.FileStation.List',
			'method' => 'list_share'
		), $settings));
	}
	public function ls(string $path, array $settings = []) {
		return $this->httpClient(array_merge(array(
			'api' => 'SYNO.FileStation.List',
			'method' => 'list',
			'folder_path' => $path
		), $settings));
	}
	public function getInodeInfo(string $path, array $settings = []) {
		return $this->httpClient(array_merge(array(
			'api' => 'SYNO.FileStation.List',
			'method' => 'getinfo',
			'path' => $path
		), $settings));
	}
	public function createSearch(string $path, array $settings = []) {
		return $this->httpClient(array_merge(array(
			'api' => 'SYNO.FileStation.Search',
			'method' => 'start',
			'path' => $path
		), $settings));
	}
	public function searchResult(string $taskId, array $settings = []) {
		return $this->httpClient(array_merge(array(
			'api' => 'SYNO.FileStation.Search',
			'method' => 'list',
			'taskid' => $taskId
		), $settings));
	}
	public function stopSearch($taskId) {
		if (is_array($taskId)) {
			foreach ($taskId as $key => $item) {
				if (!is_string($item)) {
					echo "[stopSearch]\tAt index {$key} (value: {$item}) is not a string. It's a(n) ", gettype($item), '.', PHP_EOL;
					return array('success' => false);
				}
			}
			$taskId = implode(',', $taskId);
		}
		else if (!is_string($taskId)) {
			echo '[stopSearch]: The 1st parameter must be a string array or a string.', PHP_EOL;
			return array('sucess' => false);
		}
		return $this->httpClient(array(
			'api' => 'SYNO.FileStation.Search',
			'method' => 'stop',
			'taskid' => $taskId
		));
	}
}