<?php
/*
    script to test the wikipedia snippet

    see also http://www.ibm.com/developerworks/opensource/library/x-phpwikipedia/index.html?ca=drs-
    http://www.mukudu.net/moodle/mod/wikipediasnippet/wikislurp/?secret=123456789&query=Australia&section=0&output=php

api.php?action=parse&page=Pet_door&format=json [try in ApiSandbox]

*/

require_once('wikipediasnippet.inc.php');

if ( isset($_POST['snipaddr']) && $url = $_POST['snipaddr'] ) {
    $wikisnippet = new WikipediaSnippet();

    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $wikisnippet->setUserAgent($_SERVER['HTTP_USER_AGENT']);
    }

    if (isset($_POST['proxy'])) {
        $wikisnippet->setProxy($_POST['proxy']);
    }

    if (isset($_POST['debug'])) {
        $wikisnippet->setdebugging(true);
    }

    $raw = false;
    if (isset($_POST['raw'])) {
        $raw = true;
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

    $content = $wikisnippet->getWikiContent($url, $nolinks, $noimages);

    die('<pre>' . print_r($content, true) . '</pre>');

    echo "===Start Of Snippet=====<br/>\n";
    if (isset($raw)) echo "<pre>";

    if (!$wikisnippet->error) {
        if (empty($raw)) {
            //print out some headers
            echo '<html><head><link rel="stylesheet"
                href="//bits.wikimedia.org/en.wikipedia.org/load.php?debug=false&lang=en&modules=site&only=styles&skin=vector&*"
                type="text/css" media="all" /><body>';
        }
        if ($content) {
            echo $content;
        }else{
            echo "No content returned<br/>\n";
        }
        if (empty($raw)) {
            //print out end html tags
            echo '</body></html>';
        }
    }else{
        print $wikisnippet->error ."<br/>\n";
    }

    if (isset($raw)) echo "</pre>";

    echo "===End Of Snippet=====<br/>\n";

    die();
}
?>

<!doctype html>
<html lang="en">
<head>
	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

	<title>Wikipedia Snippet Test Page</title>
</head>
<body>
    <div class="container">
        <h1>Wikipedia Snippet Test Page</h1>
        <form method="POST">
            <div class="form-group row">
            	<label for="id_snipaddr" class="col-5 col-form-label">Cut and paste the Wikipedia Snippet Address inclusing the anchor (#):</label>
            	<div class="col-7">
            		<input type='text' name='snipaddr' id="id_snipaddr" size='75' />
                	<small id="rawHelpBlock" class="form-text text-muted">
                		Enter #toc for table of contents, #preamble for the intro text and #infobox for the information box.
                	</small>
            	</div>
            </div>
            <div class="form-group row">
            	<label for="id_proxy" class="col-5 col-form-label">Enter a proxy address if required by the server - format host:port: </label>
            	<div class="col-7">
            		<input type='text' name='proxy' id="id_proxy" size='75' />
            	</div>
            </div>
            <div class="form-group row border">
            	<label for="id_images" class="col-4 col-form-label">Include Images</label>
            	<div class="col-2">
            		<input type='checkbox' name='images' id="id_images" checked />
            	</div>
            	<label for="id_links" class="col-4 col-form-label">Include Links </label>
            	<div class="col-2">
            		<input type='checkbox' name='links' id="id_links" checked />
            	</div>
            </div>
            <div class="form-group row">
            	<label for="id_debug" class="col-5 col-form-label">Run in Debug Mode </label>
            	<div class="col-7">
            		<input type='checkbox' name='debug' id="id_debug"/>
            		<small id="debugHelpBlock" class="form-text text-muted">
            			Debug information is written to webserver logs, not to screen.
            		</small>
            	</div>
            </div>
            <div class="form-group row">
            	<label for="id_raw" class="col-5 col-form-label">Return in Raw format</label>
            	<div class="col-7">
            		<input type='checkbox' name='raw' id="id_raw" />
            		<small id="rawHelpBlock" class="form-text text-muted">
            			In raw format only the wiki markup for images and links are returned, if required.
            		</small>
            	</div>
            </div>
    		<button class="btn btn-active" type='submit' name='submit' value="Submit">Submit</button>
        </form>
    	<!-- Optional JavaScript -->
    	<!-- jQuery first, then Popper.js, then Bootstrap JS -->
    	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
	</div>
</body>
</html>
