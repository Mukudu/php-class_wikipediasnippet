 <?php
/*
    Wikipedia PHP library.
    A PHP library to communicate with wikipedia through the API
    see (https://www.mediawiki.org/wiki/API:Main_page)
    to allow PHP scripts to obtain snippets of wikipedia pages.

    Feb 2021
    * Updated to use the JSON reponses.
    * Dropped support for raw output.

*/

class WikipediaSnippet {

    private $version = '20210128-01';

    private $debugging = false;

    // User-Agent: CoolToolName/0.0 (https://example.org/cool-tool/; cool-tool@example.org) used-base-library/0.0
    //<client name>/<version> (<contact information>) <library/framework name>/<version> [<library name>/<version> ...]
    private $useragent = '';
    private $http_proxy = '';
    private $wiki_api_url ='http://en.wikipedia.org/w/api.php';
    private $wiki_source_msg = 'Wikipedia - (http://en.wikipedia.org/)';
    private $reponseformat = 'json';

    // content stuff
    private $content = '';
    private $wiki_content_url = '';
    private $rawtitle = '';
    private $title = '';
    private $pageid = 0;
    private $snippets = array();         // including infobox, preamble, #sections
    private $toc = '';

    public $url = '';
    public $redirected = false;         //has this been redirected
    public $error = '';

    // constructor
    public function __construct() {
        $version = $this->version();
        $this->useragent = "WikipediaSnippet/$version (https://github.com/Mukudu/php-class_wikipediasnippet; benellis@mukudu.net) php-class_wikipediasnippet/$version";
    }

    public function version() {
        return $this->version;
    }


    public function setWikiSourceMsg($sourcemsg) {
        if ($sourcemsg) {
            if ($this->debugging) error_log('Source text is being reset to ' . $sourcemsg);
            $this->wiki_source_msg = $sourcemsg;
        }
    }

    // Set a different user agent - if no setting - current remains in force.
    public function setUserAgent($useragent) {
        if ($useragent) {
            if ($this->debugging) error_log('Useragent is being reset to ' . $useragent);
            $this->useragent = $useragent;
        }
    }

    // set a proxy for curl requests - if empty proxy is unset
    public function setProxy($proxy) {
        if ($this->debugging && $proxy) error_log("Proxy being set to '$proxy'");
        $this->http_proxy = $proxy;     //if empty proxy is unset
    }

    // set the wiki API url - if not set current stays in force
    public function setWikiAPI_URL($wiki_api) {
        if ($wiki_api) {
            if ($this->debugging) error_log("Wiki API URL set to '$wiki_api'");
            $this->wiki_api_url = $wiki_api;
        }
    }

    public function getWikiContent($url, $nolinks=false, $noimages=false, $includecitations = false) {
        $result = '';

       if ($this->debugging) error_log('DEBUG mode - please switch off in production environments');

        $webaddress = parse_url($url);   // Parse the url into parts

        if (!$webaddress) { //
            $this->_raiseError('This is not a proper URL');
        }elseif (!$webaddress['fragment']) {
            //snippet must always be specified - we do not want everyone just taking full pages of wikipedia do we??
            $this->_raiseError('A URL fragment must be specified - otherwise call wikipedia directly');
        }else{
            // we need the raw title - proper title will come from wikipedia
            $this->rawtitle = basename($webaddress['path']);
            if ($this->debugging) error_log('Raw Title computed to be ' . $this->rawtitle);

            //more url stuff for later
            $baseurl = $webaddress['scheme'].'://'.$webaddress['host'];
            $this->wiki_content_url = $baseurl . pathinfo($webaddress['path'], PATHINFO_DIRNAME) . '/';
            if ($this->debugging) error_log('Wiki Content URL determined to be ' . $this->wiki_content_url);
            $this->url = $baseurl . $webaddress['path'];
            if ($this->debugging) error_log('Request URL determined to be ' . $this->url);

            // first we need to get a table of contents
            // this may be cached locally and so save us some time
            $toc = $this->_get_toc($url);                   //an array that we can use in php - json in $this->toc  // could be empty

            //use the toc to determine the section we want if not the toc itself
            if ($webaddress['fragment'] != 'toc') {                     //is this the toc?
                if ($webaddress['fragment'] == 'infobox' || $webaddress['fragment'] == 'preamble') {
                    $section = 0;
                }else if (count($toc) == 0) {           //catches redirects as well
                    $section = 0;
                }else{
                    if (!$section=$toc[$webaddress['fragment']]) {          //if not, then we want a valid section
                        $section = $toc[strtolower($webaddress['fragment'])];       //this should never have to happen
                    }
                }

                // Create the request URL for the wikipedia API
                $cparams = array(
                    'action' => 'parse',
                    'page' => $this->rawtitle,
                    'prop' => 'text',
                    'section' => $section,
                    'format' => 'json',
                    'redirects' => '',
                    'disableeditsection' => '',
                );

                $geturl = $this->_makeWikiAPIcall($cparams);

                if ($this->debugging) error_log("Content API call being made to '$geturl'");

                if ($response = $this->_getWikiPage($geturl)) {             //make the call.

                    if ($this->debugging) error_log("Non Error response returned - making JSON Object");

                    if ($response = $this->_response2JSON($response)) { // turn the reponse into an JSON object to make it easier to work with
                        //extract the page info
                        $this->title = $response->parse->title;
                        $this->pageid = $response->parse->pageid;

                        //redirects should not happen according to the docs if you call with redirects param
                        $result = (array) $response->parse->text;
                        $result = $result['*'];
                        libxml_use_internal_errors(true);       // we want to catch any errors
                        $doc = new DOMDocument();
                        if ($doc->loadHTML($result)) {
                            // Remove images
                            if ($noimages) {
                                // Images are housed in divs named thumbs
                                $allimagenodes = $doc->getElementsByTagName('div');
                                $fnddivs = array();
                                foreach ($allimagenodes as $div) {
                                    if (in_array('thumb', (explode(' ' , $div->getAttribute('class'))))) {
                                        $fnddivs[] = $div;
                                    }
                                }
                                foreach ($fnddivs as $div) {
                                    $div->parentNode->removeChild($div);
                                }
                                // Catch any outside of that.
                                $allimages = $doc->getElementsByTagName('img');
                                $orphanimgs = array();
                                foreach($allimages as $img) {
                                    $orphanimgs[] = $img;
                                }
                                foreach ($orphanimgs as $img) {
                                    $img->parentNode->removeChild($img);
                                }
                                // And sometimes we have captions - arrgghhh
                                $allcaptions = $doc->getElementsByTagName('caption');
                                $orphancaps = array();
                                foreach($allcaptions as $img) {
                                    $orphancaps[] = $img;
                                }
                                foreach ($orphancaps as $caption) {
                                    $caption->parentNode->removeChild($caption);
                                }
                            }

                            // Remove all citation references if no citations requested.
                            if (!$includecitations) {
                                $possiblecitations = $doc->getElementsByTagName('sup');
                                $fndcitationlinks = array();
                                foreach ($possiblecitations as $citelink) {
                                    if (in_array('reference', (explode(' ',  $citelink->getAttribute('class'))))) {
                                        $fndcitationlinks[] = $citelink;
                                    }
                                }
                                foreach ($fndcitationlinks as $citelink) {
                                    if ($citelink->parentNode) {
                                        $citelink->parentNode->removeChild($citelink);
                                    }
                                }
                                // Now remove the citations themselves.
                                foreach ($doc->getElementsByTagName('div') as $div) {
                                    if (in_array('mw-references-wrap', (explode(' ',  $div->getAttribute('class'))))) {
                                        if ($div->parentNode) {
                                            $div->parentNode->removeChild($div);
                                            break;
                                        }
                                    }
                                }
                            }

                            // links
                            $alllinks = $doc->getElementsByTagName('a');
                            $changelinks = array();
                            foreach ($alllinks as $link) {
                                if ($href = $link->getAttribute('href')) {
                                    if (strpos($href, '#') === 0) {
                                        // Anchor links are allowed.
                                        continue;
                                    }
                                    if ($nolinks) {
                                        $changelinks[] = $link;
                                    } else {
                                        $pattern = '/^(http|https|ftp|mailto):\\/\\//i';
                                        $isfullurl = preg_match($pattern, $href);
                                        if(!$isfullurl){
                                            // Is it an internal link
                                            $hrefpattern = '|^/wiki/|i';
                                            if (preg_match($hrefpattern, $href)) {
                                                $href = preg_replace($hrefpattern, $this->wiki_content_url, $href);
                                                $link->setAttribute('href', $href);
                                            }
                                        }
                                        // We want all links to open in another window.
                                        $link->setAttribute('target', '_blank');
                                    }
                                }
                            }

                            if ($nolinks && count($changelinks)) {
                                foreach ($changelinks as $link) {
                                    if ($newNode = $doc->createTextNode($link->textContent)) {
                                        if ($textNode = $doc->importNode($newNode)) {
                                            // Insert it before the link.
                                            // $parentnode = $link->parentNode;
                                            $link->parentNode->insertBefore($textNode, $link);
                                            // And remove the link.
                                            $link->parentNode->removeChild($link);
                                            // echo ($parentnode->C14N());
                                        }
                                    }
                                }
                            }

                            if ($section == 0) {
                                // this is info box or premable.
                                if ($webaddress['fragment'] == 'infobox') {
                                    foreach ($doc->getElementsByTagName('table') as $table) {
                                        if (in_array('infobox', (explode(' ' ,  $table->getAttribute('class'))))) {
                                            $result = $table->C14N();
                                            break;
                                        }
                                    }
                                } else if ($webaddress['fragment'] == 'preamble') {
                                    $newresult = '';
                                    // High level paragraphs
                                    foreach ($doc->getElementsByTagName('p') as $paragraph) {
                                        $newresult .= $paragraph->C14N();
                                    }
                                    $result = $newresult;
                                }

                                if ($includecitations) {
                                    //Add in citations
                                    foreach ($doc->getElementsByTagName('div') as $div) {
                                        if (in_array('mw-references-wrap', (explode(' ',  $div->getAttribute('class'))))) {
                                            $result .= $div->C14N();
                                            break;
                                        }
                                    }
                                }
                            } else {
                                $result = '';       // Needs a reset for some reason.
                                // we only want the contents of the body tag returned.
                                $body = $doc->getElementsByTagName('body')->item(0);
                                // perform innerhtml on $body by enumerating child nodes
                                // and saving them individually
                                foreach ($body->childNodes as $childNode) {
                                    $result .= $childNode->C14N();
                                }
                            }
                        }
                    } else {
                        $this->_raiseError('Cannot Parse the response from the Wiki.');
                    }
                } else {
                    $this->_raiseError('Cannot read the output from the Wiki.');
                }
            } else {
                // we deal with toc stuff here
                if (!empty($toc)) {         //we have a valid table of contents
                    // would love to let wikpedia do all the hard work but html tocs are not returned
                    $toc_html = array();

                    if ($toc_obj = $this->_response2JSON($this->toc)) {          // get a copy
                         $last_level = 1;               //keep track of toc levels
                         $toc_html[] = '<ul>';          //start html list

                         foreach ($toc_obj->parse->sections as $section) {
                            $linknumber = 0;
                            $linktext = '';

                            $thislevel = intval($section->toclevel);
                            if ($thislevel > $last_level) {         //start a new level list
                                $toc_html[] = '<ul>';
                            }
                            if ($thislevel < $last_level) {     //end old level
                                $toc_html[] = '</ul>';
                            }
                            $last_level = $thislevel;

                            $linknumber = $section->number;
                            $linktext = $linktext . '<a href="'. $this->url .'#' . $section->anchor . '">' . $section->anchor . '</a>';

                            $toc_html[] = "<li>$linknumber. $linktext</li>";
                         }
                         $toc_html[] = '</ul>';
                    }
                    $result = implode("\n", $toc_html);
                }else{
                    //let check if we have a redirect scenario
                    //echo htmlentities($this->toc);
                    //most wikipedia pages do have a table of contents f only the references section
                    // can assume this is a redirection
                    $this->_raiseError('There does not appear to be a table of contents on this page - it may require redirection');
                }
            }
        }
        // Add in a Source message //
        if ($result) {
            $result .= "\n<p><small><em>Source: " . $this->wiki_source_msg . '</em></small></p>';
        }

        return $result;
    }

    //this one creates the API URL
    private function _makeWikiAPIcall(array $parms) {
        return($this->wiki_api_url . '?' . http_build_query($parms, null, '&'));
    }

    private function _response2JSON($json) {

        if ($this->debugging) error_log("JSON response recasting in progress");

        $jsonobject = json_decode($json);
        if (json_last_error() && $errmsg = json_last_error_msg()) {
            $this->_raiseError($errmsg);
            error_log("JSON ERROR: $errmsg");
        }

        return $jsonobject;
    }

    // function to raise Errors
    private function _raiseError($errmsg) {
        $this->error = $errmsg;
        if ($this->debugging) error_log($errmsg);
    }

    private function print_die($item, $die=true) {
        $output = '<pre>';
        if (is_scalar($item)) {
            $output .= $item;
        } else {
            $output .= print_r($item, true);
        }
        $output .= '</pre>';
        if ($die) {
            die($output);
        } else {
            echo $output;
        }
    }


    // function to get the table of contents for the wikipage
    // the toc is cached for faster access and a copy of the json is
    // always put in the objects toc property
    private function _get_toc($url) {
        if ($this->debugging) error_log("Getting Table of Contents");

        $toc_list = array();

        if (!$this->toc) {   //see if we have it already
            //toc params
            $tocparms = array();
            $tocparms['format'] = 'json';
            $tocparms['action'] = 'parse';
            $tocparms['prop'] = 'sections';
            $tocparms['page'] = $this->rawtitle;
            $tocparms['redirects'] = '';
            $toc_url = $this->_makeWikiAPIcall($tocparms);

            if ($this->debugging) error_log("TOC API call being made to '$toc_url'");

            if ($this->toc = $this->_getWikiPage($toc_url)) {
                if ($this->debugging) error_log("Non Error response returned, json being turned into object");
            }else{
                if ($this->debugging) error_log('TOC has not been returned by request');
            }
        }

        if ($this->toc && ($toc_obj = $this->_response2JSON($this->toc))) {
            foreach ($toc_obj->parse->sections as $section) {
                $toc_list[$section->anchor] = $section->index;
            }
        }

        return $toc_list;
    }

    //
    //  default with no params is true - otherwise true or false - anyother params turns it off i.e. false
    //
    public function setdebugging($on_off=true) {
        if (($on_off === false) || ($on_off === true)) {
            $this->debugging = $on_off;
        }else{
            $this->debugging=false;
        }
    }

    //
    // function to get the page from wikipedia and check for errors before returning json object
    //
    private function _getWikiPage($url, $post=false) {
        $postFields = '';
        if ($post) {        //if HTTP POST required - split the URL for the cURL lib
            list($url, $postFields) = explode('?', $url);
        }
        return($this->_getPagefromWikimedia($url, $postFields));
    }

    //
    //  function to get the raw content from wikipedia
    //
    private function _getPagefromWikimedia($url, $postFields='') {
        if ($this->debugging) error_log("Making cURL HTTP Request");
        $session = curl_init($url);

        curl_setopt( $session, CURLOPT_URL, $url );
        curl_setopt( $session, CURLOPT_USERAGENT, $this->useragent);               //wikipedia insists on a useragent
        curl_setopt( $session, CURLOPT_HEADER, false );
        curl_setopt( $session, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt( $session, CURLOPT_MAXREDIRS, 5);

        //if we need to set a proxy.check in the environment
        if ($this->http_proxy) {
            curl_setopt( $session, CURLOPT_PROXY, $this->http_proxy);
        }

        curl_setopt( $session, CURLOPT_RETURNTRANSFER, 1 );
        if (!empty($postFields)) {
            curl_setopt( $session, CURLOPT_HTTPHEADER, array('Expect:'));           //workaround for error caused by a wikipedia squid being a HTTP1.0 device -
                                                                                    //http://serverfault.com/questions/107813/why-does-squid-reject-this-multipart-form-data-post-from-curl
            curl_setopt( $session, CURLOPT_POST, 1);
            curl_setopt( $session, CURLOPT_POSTFIELDS, $postFields );
        }

        if ($this->debugging) error_log("Starting request");

        $result = curl_exec( $session );
        if ($err = curl_error($session)) {
            $err = "HTTP request error - $err";
            if ($this->debugging) error_log($err);
            $this->error = $err;
        }

        if ($this->debugging){
            $info = curl_getinfo($session);
            error_log('cURL Info: '. print_r($info, true));     //write to logs
        }

        curl_close( $session );

        return $result;
    }
}
