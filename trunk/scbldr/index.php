<?php
/*!
 * Schedule builder
 *
 * Copyright (c) 2011, Edwin Choi
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

//session_start();

define("__INTERNAL", true);

require_once "./dbconnect.php";
require_once "./terminfo.php";

date_default_timezone_set("EST");

if (isset($_GET['debug']) || true) {
	define("DEBUG", 1);
} else {
	define("DEBUG", false);
}

if ($updating_data) {
	require_once "./updating.php";
	exit;
}

if (DEBUG) {
	define("JSVER", ".nomin");
} else {
	define("JSVER", "");
}

header("Cache-Control: none")
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Schedule Finder Matcher Picker</title>
	<meta name="keywords" content="njit, schedule builder, schedule maker, scheduler" />
	<meta name="description" content="A site to help you find a schedule more easily and effectively. Or if you're feeling really lazy, this can also generate all valid combinations for a set of courses." />
	<meta name="charset" content="utf-8" />
	<meta property="og:title" content="Schedule Finder Matcher"/>
	<meta property="og:type" content="website" />

	<link rel="icon" type="image/jpeg" href="http://cp3.njit.edu/favicon.ico" />

	<link type="text/css" rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/dojo/1.6/dijit/themes/claro/claro.css" />

	<link type="text/css" rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/themes/smoothness/jquery-ui.css" />
	<!--
	<link type="text/css" rel="stylesheet" href="../css/smoothness/jquery-ui-1.8.4.custom.css" />
	-->

	<link type="text/css" rel="stylesheet" href="css/main.css" />
	<link type="text/css" rel="stylesheet" media="print" href="css/print.css" />
	<link type="text/css" rel="stylesheet" href="css/scheduleGrid.css" />
	<!--[if IE 7]>
	<link type="text/css" rel="stylesheet" href="css/ie7.css" />
	<![endif]-->
	<!--[if lt IE 8]>
	<style type="text/css">
.center { width:765px; }
	</style>
	<![endif]-->
	<style type="text/css">
#xheader table { height: 22px; }
#xheader table td { vertical-align:top; }
.ui-widget { font-family: Tahoma,Helvetica,Arial,sans-serif; font-size: 1em; }
.ui-widget .ui-widget { font-size: 1em; }
.ui-widget input, .ui-widget select, .ui-widget textarea, .ui-widget button { font-family: Tahoma,Helvetica,Arial,sans-serif; font-size: 1em; }
	</style>

	<script type="text/javascript" src="lib/raphael.js"></script>

	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.0/jquery.min.js"></script>
	<script type="text/javascript" src="lib/jquery-ui-1.8.12.custom.js"></script>

	<script type="text/javascript" src="lib/toolbox.expose.js"></script>
	<script type="text/javascript" src="lib/tools.overlay.js"></script>

	<script type="text/javascript" src="lib/dojo.js"></script>
	<script type="text/javascript" src="lib/scbldr.dojo.js"></script>

	<script>
var djConfig = { baseUrl: "lib/" };

if ($.browser.mozilla && /^2\.0/.test($.browser.version)) {
	// web workers postMessage doesn't seem to work as expected in FF 4.0... use the worker work-around object
	delete Worker;
}
	</script>

	<?php /* dump the entire database into COURSE_DATA var... memory consumption should be around 600kb */ ?>
	<script type="text/javascript" src="datasvc.php?p=/" defer></script>
	
	<script type="text/javascript" src="js/autocomplete.js" defer></script>

	<script type="text/javascript" src="js/compat.js" async></script>
	<script type="text/javascript" src="js/timegrid.js"defer></script>
	<script type="text/javascript" src="js/worker.js" async></script>

	<script type="text/javascript" src="js/messageport.js" async="async"></script>
	<script type="text/javascript" src="js/simplerpc.js"defer></script>
	<script type="text/javascript" src="js/graph.js"defer></script>
	
	<script type="text/javascript" src="js/site.js" defer></script>

	<script type="text/javascript">
dojo.require("dijit.layout.AccordionContainer");
dojo.require("dijit.layout.ContentPane");
dojo.require("dijit.Tooltip");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.ComboBox");
dojo.require("dijit.Menu");

_gaq = [];
	</script>
<?php include "ga.inc.php"; ?>
</head>
<body class="claro">
<noscript>
	<h4>JavaScript is required to view this site.</h4>
</noscript>
<div id="browser_not_supported" style="display:none;">
Your browser is not supported... time to upgrade.
</div>
<div id="page" style="display:none;">

	<div id="header">
		<nav>
			<a id="infolink" href="#">info</a>
			&ensp;|&ensp;
			<a href="https://code.google.com/p/schedule-builder/issues/list" target="_blank">issues</a>
			&ensp;|&ensp;
			<a href="http://code.google.com/p/schedule-builder/" target="_blank">source code</a>
		</nav>
		<!--
		<div style="float:right;text-align:right;padding-right:8px;">
			<a id="aboutlink" href="#">About</a>
		</div>
		-->
	</div>

	<div id="content">
		<div class="left">
			<span style="height:20px;">&nbsp;</span>
			<hr/>
			<a id="save" onclick="return false;" class="ui-state-disabled" href="#" style="float:right;margin-right:8px;display:none;">Publish</a>
			<strong>Schedule</strong>
			<ul id="scheduleSummary" class='filter-defs' style="list-style-type:none;margin:0;padding:8px;"></ul>
			<div style="text-align:right;border-top:1px #ccc solid;padding-top:4px;">
				Credits: <span id="totalCredits">0.00</span>
			</div>
		</div>

		<div class="center">
			<div id="xheader" style="height:22px;margin-left:54px;margin-bottom:5px;margin-right:2px;">
				<table cellpadding=0 cellspacing=0 style="width:100%;vertical-align:top;">
					<tr><td style="text-align:left">
						<div>
							<span id="scheduleTitle" style="font-weight:700;font-size:1.5em;"><?= $current_term_label; ?></span>
							<span id="loadingBox" style="font-size:1.1em;padding-left:0.5em;">
								<em>Loading</em>
							</span>
						</div>
						<script type="text/javascript">
						(function() {
							var loadStep = 0;
							setTimeout(function() {
								var lbox = document.getElementById("loadingBox");
								if (lbox) {
									lbox.innerHTML = "<em>Loading" + (".....".substr(0, loadStep + 1)) + "</em>";
									loadStep = (loadStep + 1) % 5;
									setTimeout(arguments.callee, 250);
								}
							}, 250);
						})();
						</script>
					</td><td style="text-align:right;">
						<div id="sflinks">
							<span id="progress" style="margin-right: 6px;"></span>
							<div id="schedactions" style="display:inline;margin-right:4px;">
								<button type="button" id="printButton" onclick="_gaq.push(['_trackEvent','print'])" title="Better printing support (does not include non-meeting courses). No support for any version of IE.">Print</button>
								<button type="button" id="clearButton">Clear</button>
							</div>
							<span class="spacer">|</span>
							<span style="font-size: 1.1em;">
								<a href="#" onclick="_loadSchedulesGraph(); return false;" title="Radial graph of the courses">Graph</a>
								/
								Find <a href="#" onclick="_gaq.push(['_trackEvent','scheduleGenerator','First']); _loadFirstSchedule(); return false;">1<sup>st</sup></a>
								/
								<a href="#" onclick="_gaq.push(['_trackEvent','scheduleGenerator','All']);_loadAllSchedules(); return false;">All</a>
							</span>
						</div>
					</td></tr>
				</table>
			</div>
			<div id="scheduleResultView" style="display:none; width:100%; margin:0;padding:0;"></div>
			<div id='scheduleView' style='margin: 0 auto;' class='sv-showrange sv-shownotes'>
			</div>
		</div>

		<div class="right">
			<form name="searchForm">
				<div style="position:relative;">
					<input id="submitButton" class="ui-icon ui-icon-search" type="button" name="searchButton" style="float:right;position:absolute;right:0;" />
					<span id="loadingIcon" style="position:absolute;float:right;z-index:1;right:25px;" class="dijitContentPaneLoading"></span>
					<input id="search_input" class="empty" type="text" name="search" value="Add course" />
				</div>
				<hr/>
				<div id="course_list" class="sec-view2 course-list"></div>
				<!--
				<div class="minipane" style="width:134px;clear:both;z-index:1000;position:relative;">
					<span class="ui-icon ui-icon-close" style="float:right;">&nbsp;</span>
					<span class="color-indicator" style="background-color:#faa;">&nbsp;</span>
					<strong>CS341</strong> <span>(3.00)</span>
					<span data-field="title" style="display:block;">FOUND OF COMP SCI II</span>
					<div class="dropdown" data-field="instructor">
						<span class="ui-icon ui-icon-triangle-1-s" style='float:right;'></span>
						<span class="field-value"><em>Instructor</em></span>
						<ul class="ui-menu" style="display:none;">
							<li>Nakayama</li>
							<li>Nakayomo</li>
						</ul>
					</div>
					<table class="section-info">
						<tr>
							<td class="sec-is-honors ui-state-disabled"><span class="ui-icon ui-icon-star">&nbsp;</span></td>
							<td class="sec-is-online"><span class="ui-icon ui-icon-clock">&nbsp;</span></td>
							<td class="sec-is-cancel ui-state-disabled"><span class="ui-icon ui-icon-cancel">&nbsp;</span></td>
							<td class="sec-comments ui-state-disabled"><span class="ui-icon ui-icon-comment">&nbsp;</span></td>
							<td class="sec-is-conflict ui-state-disabled"><span class="ui-icon ui-icon-notice">&nbsp;</span></td>
							<td class="sec-assigned-book ui-state-disabled"><span class="book-icon">&nbsp;</span></td>
						</tr>
					</table>
				</div>
				<script>
				$(function() {
					var dd = $(".minipane .dropdown");
					dd.find(".ui-menu").menu();
				});
				</script>
				-->
			</form>
		</div>
	</div>

	<div id="footer">
		<hr/>
		Copyright &copy; 2010 Edwin Choi
		<br/>
		Last updated on <?= date("D, d M Y H:i", $last_run_timestamp) ?>
		<em>(source: <?= date("d M Y H:i", $last_update_timestamp) ?>)</em>.
	</div>

	<div id="searchTree" class="ui-state-default" style="display:none;position:absolute;top:12px;background:#fff;margin:0 auto;">
		<div class="ui-widget-header" style="padding:5px;">
			Graph
		</div>
		<div class="ui-widget-content">
			<div class="cl" style="width:200px;float:left;">
				The lines in this graph depict non-conflicting sections.
				<br/>
				The more courses you have, the more complex this graph will be.
			</div>
			<div class="cr" style="width:200px;float:right;"></div>
			<div class="cm" style="background:#333;margin:0 auto;text-align:center;float:left;"></div>
		</div>
	</div>

	<div id='tooltip' style="display:none;">
		<h4 class="tt-title">
			<span class="title"></span>
			<span style="display:block" class='cancelled'>CANCELLED</span>
		</h4>
		<ul class='tt-items'>
			<li class='tt-callnr'><span class="hdr">Call #:</span> <span></span></li>
			<li class='tt-section'><span class="hdr">Section:</span> <span></span></li>
			<li class='tt-seats'><span class="hdr">Seats:</span> <span></span></li>
			<li class='tt-instructor'><span class="hdr">Instructor:</span> <span ></span></li>
			<li class='tt-comments'><span class="hdr">Comments:</span> <div style="padding-left:8px;"></div></li>
		</ul>
	</div>
</div>

<div id="infopanel">
	<iframe src="overview.html" style="border:none;padding:0;margin:0;width:100%;height:760px;"></iframe>
</div>

<div id="imagemodal"></div>
<div id="messagePanel" style="display:none;width:362px;" class="dialog">
	<img height=12 width=12 />
	<strong class="dialog-title"></strong>
	<hr/>
	<div class="dialog-message"></div>
	<div style="text-align:right;margin-top:4px;">
		<button class="close">Close</button>
	</div>
</div>
</body>
</html>
