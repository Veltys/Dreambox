<?php


/**
 *
 * @file:			Dreambox.php
 * @brief:			Dreambox Enigma2 playlist extractor and transformer
 * @author:			Veltys
 * @author:			robertut
 * @date:			2022-04-28
 * @version:		2.0.0
 * @note:			Usage: Put on a webserver an visit its URL
 * @note:			Original file from ➡️ https://forums.openpli.org/topic/29950-enigma2-channels-list-to-vlc-playlist-converter-php/#entry366485
 * @note:			Based on openwebif api at http://e2devel.com/apidoc/webif/#getallservices
 */


/**
 * Clase Dreambox
 * Contiene la configuración necesaria agrupada en un objeto
 */
class Dreambox {
	// @formatter:off

	public string $ipAddress;													///< IP address of the Enigma2 box on the network, with access to the web interface
	public string $user;														///< User with access to the web interface
	public string $password;													///< Password with access to the web interface
	public bool $https;															///< Use secure (https) protocol
	public string $urlAllServices;												///< URL for "all services"
	public int $port;															///< Port of the streaming proxy
	public string $streamAddress;												///< Entire http address and port of the streaming proxy
	public string $playlistFilename;											///< The name of the playlist file, extension will be added automatically

	// @formatter:on


	/**
	 * Constructor de clase
	 * Inicializa el objeto
	 */
	function __construct() {
		// @formatter:off

		$this->ipAddress 		= '';

		$this->user 			= '';
		$this->password 		= '';

		$this->https 			= true;

		$this->urlAllServices 	= 'http' . ($this->https ? 's' : '') . '://' . ($this->user != '' && $this->password != '' ? $this->user . ':' . $this->password . '@' : '') . $this->ipAddress . ':' . '/web/getallservices';

		$this->port 			= '8001';

		$this->streamAddress 	= 'http' . ($this->https ? 's' : '') . '://' . ($this->user != '' && $this->password != '' ? $this->user . ':' . $this->password . '@' : '') . $this->ipAddress . ':' . $this->port . '/';

		$this->playlistFilename = "";

		// @formatter:on
	}
}


/**
 * Interfaz iPlaylist
 * Especifica los prototipos de los métodos comunes a cada uno de los formatos
 */
interface iPlaylist {


	/**
	 * Función addServicio()
	 * Añade un servicio a la lista de reproducción
	 *
	 * @param	string	nombre		Nombre del servicio
	 * @param	string	dirección	Dirección del servicio
	 */
	public function addServicio();


	/**
	 * Función getExtension($ext)
	 * Observador de la variable homónima
	 *
	 * @retval	string				Extensión de la lista de reproducción
	 */
	public function getExtension();


	/**
	 * Función pie()
	 * Añade el pie (terminador) de la lista de reproducción
	 */
	public function pie();
}


/**
 * Clase abstracta aPlaylist
 * Implementa la interfaz iPlayList
 * Continúa especificando los prototipos de los métodos comunes a cada uno de los formatos
 */
abstract class aPlaylist implements iPlaylist {


	// @formatter:off

	protected string $_extension;												///< Texto con la extension de la lista de reproducción
	protected string $_playlist;												///< Texto de la lista de reproducción

	// @formatter:on


	/**
	 * Función _encabezado()
	 * Inicializa la playlist con el encabezado adecuado al formato
	 */
	protected abstract function _encabezado();


	/**
	 * Función _setExtension($ext)
	 * Modificador de la variable homónima
	 *
	 * @param	string	ext			Extensión de la lista de reproducción
	 */
	protected function _setExtension($ext) {
		$this->_extension = $ext;
	}


	/**
	 * Función getExtension($ext)
	 * Observador de la variable homónima
	 *
	 * @retval	string				Extensión de la lista de reproducción
	 */
	public function getExtension() {
		return $this->_extension;
	}


	/**
	 * Métodos mágico __tostring()
	 * Retorna el contenido de la lista de reproducción
	 *
	 * @retval	string				Contenido de la lista de reproducción
	 */
	public function __toString() {
		return $this->_playlist;
	}
}


/**
 * Clase playlistXspf
 * Extiende la clase abstracta aPlaylist
 * Genera una lista de reproducción en formato xspf
 * Al acabar de añadir servicios, es necesario terminarla llamando a la función pie()
 */
class playlistXspf extends aPlaylist {


	/**
	 * Constructor de clase
	 * Inicializa el objeto
	 */
	function __construct() {
		$this->_encabezado();
		$this->_setExtension('xspf');
	}


	/**
	 * Función _encabezado()
	 * Inicializa la playlist con el encabezado adecuado al formato xspf
	 */
	protected function _encabezado() {
		$this->_playlist = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<playlist xmlns="http://xspf.org/ns/0/" xmlns:vlc="http://www.videolan.org/vlc/playlist/ns/0/" version="1">' . PHP_EOL . '	<title>TV Channels</title>' . PHP_EOL . '	<trackList>' . PHP_EOL;
	}


	/**
	 * Función addServicio()
	 * Añade un servicio a la lista de reproducción xspf
	 *
	 * @param	string	nombre		Nombre del servicio
	 * @param	string	dirección	Dirección del servicio
	 */
	public function addServicio() {
		if(func_num_args() == 1) {
			$this->_playlist .= '		<track>' . PHP_EOL . '			<location></location>' . PHP_EOL . '			<title>' . func_get_arg(0) . '</title>' . PHP_EOL . '		</track>' . PHP_EOL;
		}
		elseif(func_num_args() == 2) {
			$this->_playlist .= '		<track>' . PHP_EOL . '			<location>' . func_get_arg(1) . '</location>' . PHP_EOL . '			<title>' . func_get_arg(0) . '</title>' . PHP_EOL . '		</track>' . PHP_EOL;
		}
	}


	/**
	 * Función pie()
	 * Añade el pie (terminador) de la lista de reproducción xspf
	 */
	public function pie() {
		$this->_playlist .= '	</trackList>' . PHP_EOL . '</playlist>' . PHP_EOL;
	}
}


/**
 * Clase playlistM3u
 * Extiende la clase abstracta aPlaylist
 * Genera una lista de reproducción en formato m3u
 * Al acabar de añadir servicios, es necesario terminarla llamando a la función pie()
 */
class playlistM3u extends aPlaylist {


	/**
	 * Constructor de clase
	 * Inicializa el objeto
	 */
	function __construct() {
		$this->_encabezado();
		$this->_setExtension('m3u');
	}


	/**
	 * Función _encabezado()
	 * Inicializa la playlist con el encabezado adecuado al formato m3u
	 */
	protected function _encabezado() {
		$this->_playlist = '#EXTM3U' . PHP_EOL;
	}


	/**
	 * Función addServicio()
	 * Añade un servicio a la lista de reproducción m3u
	 *
	 * @param	string	nombre		Nombre del servicio
	 * @param	string	dirección	Dirección del servicio
	 */
	public function addServicio() {
		if(func_num_args() == 2) {
			$this->_playlist .= '#EXTINF:0 tvg-id="ext" group-title="Channels",' . func_get_arg(0) . PHP_EOL . func_get_arg(1) . PHP_EOL;
		}
	}


	/**
	 * Función pie()
	 * Añade el pie (terminador) de la lista de reproducción m3u
	 */
	public function pie() {
		// El formato m3u no necesita pie
	}
}


function main() {
	$dreambox = new Dreambox();


	if(isset($_REQUEST["xspf"]) || isset($_REQUEST["m3u"])) {
		$allsvc = simplexml_load_file($dreambox->urlAllServices);

		if(isset($_REQUEST["xspf"]) && $_REQUEST["xspf"] === "save") {
			$playlist = new playlistXspf();
		}
		elseif(isset($_REQUEST["m3u"]) && $_REQUEST["m3u"] === "save") {
			$playlist = new playlistM3u();
		}

		foreach($allsvc->e2bouquet as $e2bouquet) {
			$e2bouquet_name = $e2bouquet->e2servicename;

			$playlist->addServicio($e2bouquet_name);

			foreach($e2bouquet->e2servicelist->e2service as $e2service) {
				if(strstr($e2service->e2servicereference, "1:64") != false) {
					$playlist->addServicio($e2service->e2servicename);
				}
				else {
					$playlist->addServicio($e2service->e2servicename, $dreambox->streamAddress . $e2service->e2servicereference);
				}
			}

			$playlist->pie();
		}

		header('Content-Type: plain/text');
		header('Content-disposition: attachment; filename=' . $dreambox->playlistFilename . '.' . $playlist->getExtension());

		print($playlist);
	}
	else {
		$html = <<<EOS
	<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			<title>Enigma2 to VLC</title>
		</head>
		<body>
			<h1>Enigma2 Channels List Converter</h1>
			<p>This PHP script will download the channels list from your Enigma2 box at <span style="font-weight: 700; font-style: italic;">$dreambox->ipAddress</span> and convert them to an XSPF playlist for VLC player.</p>
			<p>The stream URLs will point to the address <span style="font-weight: 700; font-style: italic;">$dreambox->streamaddress</span> inside the playlist.<br />To modify the box and the URL addresses, please edit this PHP script on your server.</p>
			<p>Please note that if the channels list on the box is big (eg. rotor list), it may take a couple of seconds to process the conversion.</p>
			<p><a href="${_SERVER['REQUEST_URI']}?xspf=save">Click here to save the XSPF playlist on your PC</a><br />
			<a href="${_SERVER['REQUEST_URI']}?m3u=save">Click here to save the M3U playlist on your PC</a></p>
		</body>
	</html>
	EOS;

		print($html);
	}
}

main();

?>