<?php

/*
 * @file:           Dreambox.php
 * @brief:          Dreambox Enigma2 playlist extractor and transformer
 * @author:         Veltys
 * @originalAuthor: robertut
 * @date:           2021-09-13
 * @version:        1.0.0
 * @usage:          Put on a webserver an visit its URL
 * @note:           Original file from ➡️ https://forums.openpli.org/topic/29950-enigma2-channels-list-to-vlc-playlist-converter-php/#entry366485
 * @note:           Based on openwebif api at http://e2devel.com/apidoc/webif/#getallservices
 */


class Dreambox {
    public $ip;
    public $user;
    public $password;
    public $https;
    public $urlAllServices;
    public $port;
    public $streamAddress;
    public $playlistFilename;


    function __construct() {
        // IP address of the Enigma2 box on the network, with access to the web interface
        $this->ip               = '***REMOVED***';

        // User and password with access to the web interface
        $this->user             = '***REMOVED***';
        $this->password         = '***REMOVED***';

        // Use secure (https) protocol
        $this->https            = true;

        // URL for "all services"
        $this->urlAllServices   = 'http' . ($this->https ? 's' : '') . '://' . ($this->user != '' && $this->password != '' ? $this->user . ':' . $this->password . '@' : '') . $this->ip . ':' . '/web/getallservices';

        // Port of the streaming proxy
        $this->port             = '8001';

        // Entire http address and port of the streaming proxy
        $this->streamAddress    = 'http' . ($this->https ? 's' : '') . '://' . ($this->user != '' && $this->password != '' ? $this->user . ':' . $this->password . '@' : '') . $this->ip . ':' . $this->port . '/';

        // the name of the playlist file, extension will be added automatically
        $this->playlistFilename = "***REMOVED***";
    }
}

$dreambox = new Dreambox();

if(isset($_GET["xspf"]) || isset($_GET["m3u"])) {
    $allsvc = simplexml_load_file($dreambox->urlAllServices);
    
    $i = 0;
    
    if(isset($_GET["xspf"]) && $_GET["xspf"] === "save") {
        $xspf = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<playlist xmlns="http://xspf.org/ns/0/" xmlns:vlc="http://www.videolan.org/vlc/playlist/ns/0/" version="1">' . PHP_EOL . '	<title>TV Channels</title>' . PHP_EOL . '	<trackList>' . PHP_EOL;

        foreach($allsvc->e2bouquet as $e2bouquet) {
            $e2bouquet_name=$e2bouquet->e2servicename;

            $xspf .= '		<track>' . PHP_EOL . '			<location></location>' . PHP_EOL . '			<title>' . $e2bouquet_name . '</title>' . PHP_EOL . '		</track>' . PHP_EOL;

            foreach ($allsvc->e2bouquet[$i]->e2servicelist->e2service as $e2service) {
                $e2service_refr=$e2service->e2servicereference;
                $e2service_name=$e2service->e2servicename;

                if(strstr($e2service_refr, "1:64") != false) {
                    $xspf .= '		<track>' . PHP_EOL . '			<location></location>' . PHP_EOL . '			<title>' . $e2service_name . '</title>' . PHP_EOL . '		</track>' . PHP_EOL;
                }
                else {
                    $xspf .= '		<track>' . PHP_EOL . '			<location>' . $dreambox->streamAddress . $e2service_refr . '</location>' . PHP_EOL . '			<title>' . $e2service_name . '</title>' . PHP_EOL . '		</track>' . PHP_EOL;
                }
            }

            $i++;

            $xspf .= '	</trackList>' . PHP_EOL . '</playlist>' . PHP_EOL;

        }

        header('Content-Type: plain/text');
        header('Content-disposition: attachment; filename=' . $dreambox->playlistFilename . '.xspf');

        print $xspf;
    }
    elseif(isset($_GET["m3u"]) && $_GET["m3u"] === "save") {
        $m3u = '#EXTM3U' . PHP_EOL;

        foreach($allsvc->e2bouquet as $e2bouquet) {
            $e2bouquet_name=$e2bouquet->e2servicename;

            foreach ($allsvc->e2bouquet[$i]->e2servicelist->e2service as $e2service) {
                $e2service_refr=$e2service->e2servicereference;
                $e2service_name=$e2service->e2servicename;

                if(strstr($e2service_refr, "1:64") == false) {
                    $m3u .= '#EXTINF:0 tvg-id="ext" group-title="Channels",' . $e2service_name . PHP_EOL . $dreambox->streamAddress . $e2service_refr . PHP_EOL;
                }
            }

            $i++;
        }

        header('Content-Type: plain/text');
        header('Content-disposition: attachment; filename=' . $dreambox->playlistFilename . '.m3u');

        print $m3u;
    }
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
		<p>This PHP script will download the channels list from your Enigma2 box at <span style="font-weight: 700; font-style: italic;">$dreambox->ip</span> and convert them to an XSPF playlist for VLC player.</p>
		<p>The stream URLs will point to the address <span style="font-weight: 700; font-style: italic;">$dreambox->streamaddress</span> inside the playlist.<br />To modify the box and the URL addresses, please edit this PHP script on your server.</p>
		<p>Please note that if the channels list on the box is big (eg. rotor list), it may take a couple of seconds to process the conversion.</p>
		<p><a href="${_SERVER['REQUEST_URI']}?xspf=save">Click here to save the XSPF playlist on your PC</a><br />
		<a href="${_SERVER['REQUEST_URI']}?m3u=save">Click here to save the M3U playlist on your PC</a></p>
	</body>
</html>
EOS;
    
    print($html);
}

?>