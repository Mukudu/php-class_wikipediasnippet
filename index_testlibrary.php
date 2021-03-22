<?php
/*
    Script to test the wikipediasnippet class.
    See also http://www.ibm.com/developerworks/opensource/library/x-phpwikipedia/index.html?ca=drs.
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

    $noimages = true;
    if (isset($_POST['images'])) {
        $noimages = false;
    }

    $nolinks = true;
    if (isset($_POST['links'])) {
        $nolinks = false;
    }

    $includecitations = false;
    if (isset($_POST['citations'])) {
        $includecitations = true;
    }

    $content = $wikisnippet->getWikiContent($url, $nolinks, $noimages, $includecitations);

    if (!$wikisnippet->error) {
        $lineend = "<br />\n";
        //print out some headers
//         echo '<html><head><link rel="stylesheet"
//             href="//bits.wikimedia.org/en.wikipedia.org/load.php?debug=false&lang=en&modules=site&only=styles&skin=vector&*"
//             type="text/css" media="all" /><body>' . "\n";

        echo '<html><head><body>' . "\n";

        echo "----- Version: " . $wikisnippet->version() . "-----$lineend";

        echo "===Start Of Snippet=====$lineend";
        if ($content) {
            echo $content;
        }else{
            echo "No content returned $lineend";
        }
        echo "===End Of Snippet=====$lineend";

        //print out end html tags
        echo '</body></html>' . "\n";
    }else{
        die($wikisnippet->error . "\n");
    }
    exit(0);
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
<p>Include Images &nbsp;<input type='checkbox' name='images' checked /> &nbsp; Include Links <input type='checkbox' name='links' checked />
&nbsp; Include Citations <input type='checkbox' name='citations'/></p>
<p>Run in Debug Mode <input type='checkbox' name='debug' /> &nbsp; Debug information is written to webserver logs, not to screen.</p>
<p><input type='submit' name='submit' value="Submit"></p>
</form>
</body>
</html>
