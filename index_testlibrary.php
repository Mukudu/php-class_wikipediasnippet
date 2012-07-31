<?php
/*
    script to test the wikipedia snippet

    see also http://www.ibm.com/developerworks/opensource/library/x-phpwikipedia/index.html?ca=drs-
    http://www.mukudu.net/moodle/mod/wikipediasnippet/wikislurp/?secret=123456789&query=Australia&section=0&output=php


*/

require_once('wikipediasnippet.inc.php');

if ( isset($_POST['snipaddr']) && $url = $_POST['snipaddr'] ) {
    $wikisnippet = new WikipediaSnippet();

    if (isset($_POST['proxy'])) {
        $wikisnippet->setProxy($_POST['proxy']);
    }

    if (isset($_POST['debug'])) {
        $wikisnippet->setdebugging(true);
    }

    if (isset($_POST['raw'])) {
        $wikisnippet->setRawOutput(true);
    }

    $noimages = true;
    if (isset($_POST['images'])) {
        $noimages = false;
    }

    $nolinks = true;
    if (isset($_POST['links'])) {
        $nolinks = false;
    }

    $content = $wikisnippet->getWikiContent($url,$nolinks,$noimages);

    echo "===Start Of Snippet=====";
    if ($raw) echo "<pre>";
    if (!$wikisnippet->error) {
        if (!$raw) {
            //print out some headers
            echo '<html><head><link rel="stylesheet"
                href="//bits.wikimedia.org/en.wikipedia.org/load.php?debug=false&lang=en&modules=site&only=styles&skin=vector&*"
                type="text/css" media="all" /><body>';
        }
        if ($content) {
            echo $content;
        }else{
            echo 'No content returned';
        }
        if (!$raw) {
            //print out end html tags
            echo '</body></html>';
        }
    }else{
        print $wikisnippet->error ."<br/>\n";
    }

    if ($raw) echo "</pre>";

    echo "===End Of Snippet=====";
    exit;
}
?>

<html>
<head>
<title>Wikipedia Snippet Test Page</title>
</head>
<body>
<h1>Wikipedia Snippet Test Page</h1>
<form method="POST">
Cut and paste the Wikipedia Snippet Address inclusing the anchor (#): &nbsp; <input type='text' name='snipaddr' size='50' /><br />
<p>Enter #toc for table of contents, #preamble for the intro text and #infobox for the information box.</p>
<p>Enter a proxy address if required by the server - format host:port: &nbsp; <input type='text' name='proxy' size='75' /><br />
<p>Include Images &nbsp<input type='checkbox' name='images' checked /> &nbsp; Include Links <input type='checkbox' name='links' checked /></p>
<p>Run in Debug Mode <input type='checkbox' name='debug' /> &nbsp; Debug information is written to webserver logs, not to screen.</p>
<p>Return in Raw format <input type='checkbox' name='raw'/> &nbsp; In raw format only the wiki markup for images and links are returned, if required.</p>
<p><input type='submit' name='submit' value="Submit"></p>
</form>
</body>
</html>
