<?php


/**
 *
 * @file:			Dreambox.php
 * @brief:			Dreambox Enigma2 playlist extractor and transformer
 * @author:			Veltys
 * @author:			robertut
 * @date:			2022-12-29
 * @version:		2.1.1
 * @note:			Usage: Put on a webserver an visit its URL
 * @note:			Original file from ➡️ https://forums.openpli.org/topic/29950-enigma2-channels-list-to-vlc-playlist-converter-php/#entry366485
 * @note:			Based on openwebif api at http://e2devel.com/apidoc/webif/#getallservices
 */


/**
 * Dreambox class
 * Contains the necessary configuration grouped in an object
 */
class Dreambox {
	// @formatter:off

	public string	$ipAddress;													///< IP address of the Enigma2 box on the network, with access to the web interface
	public string	$user;														///< User with access to the web interface
	public string	$password;													///< Password with access to the web interface
	public bool		$https;														///< Use secure (https) protocol
	public string	$urlAllServices;											///< URL for "all services"
	public int		$port;														///< Port of the streaming proxy
	public string	$streamAddress;												///< Entire http address and port of the streaming proxy
	public string	$playlistFilename;											///< The name of the playlist file, extension will be added automatically

	// @formatter:on


	/**
	 * Class constructor
	 * Initializes the object
	 */
	function __construct() {
		// @formatter:off

		$this->ipAddress		= '';
		$this->user				= '';
		$this->password			= '';
		$this->https			= true;
		$this->port				= '8001';
		$this->streamAddress	= 'http' . ($this->https ? 's' : '') . '://' . ($this->user != '' && $this->password != '' ? $this->user . ':' . $this->password . '@' : '') . $this->ipAddress . ':' . $this->port . '/';
		$this->urlAllServices	= $this->streamAddress . 'web/getallservices';
		$this->playlistFilename	= 'services';

		// @formatter:on
	}
}


/**
 * iPlaylist interface
 * Specifies the prototypes of the methods common to each of the formats
 */
interface iPlaylist {


	/**
	 * addService() Function
	 * Adds a service to the playlist
	 *
	 * @param	string	name		Service name
	 * @param	string	address		Service address
	 */
	public function addService();


	/**
	 * getExtension($ext) function
	 * _extension variable getter
	 *
	 * @retval	string				Playlist extension
	 */
	public function getExtension();


	/**
	 * Función footer()
	 * Add the footer (terminator) of the playlist
	 */
	public function footer();
}


/**
 * Abstract class aPlaylist
 * Implements the iPlayList interface
 * Continues specifying the prototypes of the methods common to each of the formats
 */
abstract class aPlaylist implements iPlaylist {


	// @formatter:off

	protected string $_extension;												///< Playlist extension string
	protected string $_playlist;												///< Playlist string

	// @formatter:on


	/**
	 * _header() function
	 * Initializes the playlist with the appropriate header for the format
	 */
	protected abstract function _header();


	/**
	 * _setExtension($ext) function
	 * _extension variable setter
	 *
	 * @param	string	ext			Playlist extension
	 */
	protected function _setExtension($ext) {
		$this->_extension = $ext;
	}


	/**
	 * getExtension() function
	 * _extension variable getter
	 *
	 * @retval	string				Playlist extension
	 */
	public function getExtension() {
		return $this->_extension;
	}


	/**
	 * __tostring() magic method
	 * Returns the content of the playlist
	 *
	 * @retval	string				Playlist content
	 */
	public function __toString() {
		return $this->_playlist;
	}
}


/**
 * playlistXspf class
 * Extends the abstract class aPlaylist
 * Generates a playlist in xspf format
 * When adding services is finished, it is necessary to finish it by calling the footer() function
 */
class playlistXspf extends aPlaylist {


	/**
	 * Class constructor
	 * Initialize the object
	 */
	function __construct() {
		$this->_header();
		$this->_setExtension('xspf');
	}


	/**
	 * _header() function
	 * Initializes the playlist with the appropriate header for the xspf format
	 */
	protected function _header() {
		$this->_playlist = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<playlist xmlns="http://xspf.org/ns/0/" xmlns:vlc="http://www.videolan.org/vlc/playlist/ns/0/" version="1">' . PHP_EOL . '	<title>TV Channels</title>' . PHP_EOL . '	<trackList>' . PHP_EOL;
	}


	/**
	 * addService() function
	 * Add a service to the xspf playlist
	 *
	 * @param	string	name		Service name
	 * @param	string	address		Service address
	 */
	public function addService() {
		if(func_num_args() == 1) {
			$this->_playlist .= '		<track>' . PHP_EOL . '			<location></location>' . PHP_EOL . '			<title>' . func_get_arg(0) . '</title>' . PHP_EOL . '		</track>' . PHP_EOL;
		}
		elseif(func_num_args() == 2) {
			$this->_playlist .= '		<track>' . PHP_EOL . '			<location>' . func_get_arg(1) . '</location>' . PHP_EOL . '			<title>' . func_get_arg(0) . '</title>' . PHP_EOL . '		</track>' . PHP_EOL;
		}
	}


	/**
	 * footer() function
	 * Add footer (terminator) of xspf playlist
	 */
	public function footer() {
		$this->_playlist .= '	</trackList>' . PHP_EOL . '</playlist>' . PHP_EOL;
	}
}


/**
 * playlistM3u class
 * Extends the abstract class aPlaylist
 * Generate a playlist in m3u format
 * When adding services is finished, it is necessary to finish it by calling the footer() function
 */
class playlistM3u extends aPlaylist {


	/**
	 * Class constructor
	 * Initialize the object
	 */
	function __construct() {
		$this->_header();
		$this->_setExtension('m3u');
	}


	/**
	 * _header() function
	 * Initializes the playlist with the appropriate header for the m3u format
	 */
	protected function _header() {
		$this->_playlist = '#EXTM3U' . PHP_EOL;
	}


	/**
	 * addService() function
	 * Add a service to the m3u playlist
	 *
	 * @param	string	name		Service name
	 * @param	string	address		Service address
	 */
	public function addService() {
		if(func_num_args() == 2) {
			$this->_playlist .= '#EXTINF:0 tvg-id="ext" group-title="Channels",' . func_get_arg(0) . PHP_EOL . func_get_arg(1) . PHP_EOL;
		}
	}


	/**
	 * footer() function
	 * Add footer (terminator) of m3u playlist
	 */
	public function footer() {
		// m3u format does not need a footer
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

		makePlaylist($dreambox->streamAddress, $allsvc, $playlist);

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


/**
 * makePlaylist() function
 * Make the playlist
 *
 * @param	string	url			Service URL
 * @param	string	allsvc		Class with all services
 * @param	string	playlist	Playlist to make
 */
function makePlaylist($url, $allsvc, $playlist) {
	foreach($allsvc->e2bouquet as $e2bouquet) {
		$e2bouquet_name = $e2bouquet->e2servicename;

		$playlist->addService($e2bouquet_name);

		foreach($e2bouquet->e2servicelist->e2service as $e2service) {
			if(strstr($e2service->e2servicereference, "1:64") != false) {
				$playlist->addService($e2service->e2servicename);
			}
			else {
				$playlist->addService($e2service->e2servicename, $url . $e2service->e2servicereference);
			}
		}

		$playlist->footer();
	}
}


main();

?>