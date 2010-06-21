<?php

	function downloadRunResults($run_id)
	{
		$result = mysql_queryf("SELECT job_id,runs.name,jobs.name,jobs.created FROM runs,jobs WHERE runs.job_id=jobs.id AND runs.id=%s LIMIT 1", $run_id);

		if ( $row = mysql_fetch_array($result) ) {
			$job_id = $row[0];
			$test_suite = $row[1];
			$job_name = $row[2];
			$creation_time = $row[3];
		}

		$result = mysql_queryf("SELECT results FROM run_client WHERE run_id=%s", $run_id);

		$zip = new ZipArchive;
		$archive = $zip->open('/tmp/test.zip', ZipArchive::OVERWRITE);

		if ($archive !== TRUE)
			return;

		$i = 0;
		$qunit = FALSE;
		$qunit_header = "";
		$qunit_useragent = "";
		$qunit_tests = "";
		$qunit_summary = "";
		$qunit_merged_tests = "";
		$merged_report =
			"<html>\n<head>" .
			"<link rel=stylesheet type=text/css href=\"css/qunit.css\" media=screen>" .
			"<script type=\"text/javascript\" src=\"js/jquery.js\"></script>" .
			"<script type=\"text/javascript\" src=\"js/report.js\"></script>" .
			"<script type=\"text/javascript\">$(document).ready(function(){initQunitReport();});</script>" .
			"</head>\n<body>\n";


		while ( $row = mysql_fetch_array($result) )
		{
			$decoded_report = gzdecode($row[0]);
			$report = preg_replace("/href=\"\S*qunit.css\"/",
				"href=\"css/qunit.css\"", $decoded_report);

			if ($report != $decoded_report) {
				$qunit = TRUE;
			}

			$match = preg_match("/<H1 id=\"qunit-header\">(.*)<\/H1>/i",
				$report, $matches);

			if ($match == TRUE && strlen($qunit_header) < 1)
				$qunit_header = $matches[1];

			$match = preg_match("/<H2 id=\"{0,1}qunit-userAgent\"{0,1}>(.*)<\/H2>/i",
				$report, $matches);

			if ($match == TRUE)
				$qunit_useragent = $matches[0];

			$report = preg_replace("/<OL id=\"qunit-tests\" style=\"display: block\">/",
				"<ol id=\"qunit-tests\" style=\"display: block; \">", $report);

			$report = preg_replace("/<OL style=\"DISPLAY: block\" id=qunit-tests>/",
				"<ol id=\"qunit-tests\" style=\"display: block; \">", $report);

			$match = preg_match("/<ol id=\"qunit-tests\" style=\"display: block; \">(.*)<\/ol>/i",
				$report, $matches);

			if ($match == TRUE) {
				$qunit_tests = $matches[1];
			}

			$match = preg_match("/<p id=\"{0,1}qunit-testresult\"{0,1} class=\"{0,1}result\"{0,1}>(.*)<\/p>/i",
				$report, $matches);

			if ($match == TRUE) {
				$qunit_summary = $matches[1];
			}

			$qunit_merged_tests .= "<h2 id=\"qunit-userAgent\">$qunit_useragent</h2>";
			$qunit_merged_tests .= "<ol id=\"qunit-tests\" style=\"display: block; \">$qunit_tests</ol>";
			$qunit_merged_tests .= "<p id=\"qunit-testresult\" class=\"result\">$qunit_summary</p>";
		}
		
		$merged_report .= "<h1 id=\"qunit-header\">$qunit_header</h1>";
		$merged_report .= "<h2 id=\"qunit-banner\" class=\"qunit-pass\"></h2>";
		$merged_report .= $qunit_merged_tests;
		$merged_report .= "</body></html>";

		$zip->addFromString("report.html", $merged_report);

		if ($qunit) {
			$zip->addEmptyDir('/css');
			$zip->addFile('../css/qunit.css', '/css/qunit.css');
			$zip->addEmptyDir('/js');
			$zip->addFile('../js/jquery.js', '/js/jquery.js');
			$zip->addFile('../js/report.js', '/js/report.js');
		}

		$zip->close();

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="results.zip"');
		header('Content-Transfer-Encoding: binary');
		readfile('/tmp/test.zip');
	}
?>
