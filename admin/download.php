<?php

	function generateTestReport($run_id, $view)
	{
		$result = mysql_queryf("SELECT job_id,runs.name,jobs.name,jobs.created FROM runs,jobs WHERE runs.job_id=jobs.id AND runs.id=%s LIMIT 1", $run_id);

		if ( $row = mysql_fetch_array($result) ) {
			$job_id = $row[0];
			$test_suite = $row[1];
			$job_name = $row[2];
			$creation_time = $row[3];
		}

		$result = mysql_queryf("SELECT results FROM run_client WHERE run_id=%s", $run_id);

		$dtd_string = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n" .
			"\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";

		$dom_implementation = new DOMImplementation();
		$dtd = $dom_implementation->createDocumentType('html',
			'-//W3C//DTD XHTML 1.0 Strict//EN',
			'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd');

		$report = $dom_implementation->createDocument('', '', $dtd);

		$html = $report->createElement('html');
		$html = $report->appendChild($html);

		$head = $report->createElement('head');
		$head = $report->appendChild($head);

		$title = $report->createElement('title');
		$title->nodeValue = "$job_name";
		$title = $head->appendChild($title);

		$resource_path = "";

		if ($view)
			$resource_path = "../";

		$qunit_css = $report->createElement('link');
		$qunit_css->setAttribute('rel', 'stylesheet');
		$qunit_css->setAttribute('href', $resource_path . 'css/qunit.css');
		$qunit_css->setAttribute('type', 'text/css');
		$qunit_css->setAttribute('media', 'screen');
		$qunit_css = $head->appendChild($qunit_css);

		$jquery_js = $report->createElement('script');
		$jquery_js->setAttribute('type', 'text/javascript');
		$jquery_js->setAttribute('src', $resource_path . 'js/jquery.js');
		$jquery_js = $head->appendChild($jquery_js);

		$report_js = $report->createElement('script');
		$report_js->setAttribute('type', 'text/javascript');
		$report_js->setAttribute('src', $resource_path . 'js/report.js');
		$report_js = $head->appendChild($report_js);

		$ready_js = $report->createElement('script');
		$ready_js->setAttribute('type', 'text/javascript');
		$ready_js->nodeValue = "$(document).ready(function(){initQunitReport();});";
		$ready_js = $head->appendChild($ready_js);

		$body = $report->createElement('body');
		$body = $report->appendChild($body);

		$report_qunit_header = $report->createElement('h1');
		$report_qunit_header->setAttribute('id', 'qunit-header');
		$report_qunit_header->nodeValue = "QUnit Test Report: $job_name";
		$report_qunit_header = $body->appendChild($report_qunit_header);

		while ( $row = mysql_fetch_array($result) )
		{
			/* prepend a correct DTD otherwise the PHP DOM parser won't work correctly */
			$report_html = $dtd_string . gzdecode($row[0]);

			$dom = new domDocument;
			$dom->loadHTML($report_html);
			$dom->preserveWhiteSpace = false;

			$qunit_header = $dom->getElementById('qunit-header');
			$qunit_banner = $dom->getElementById('qunit-banner');
			$qunit_useragent = $dom->getElementById('qunit-userAgent');
			$qunit_tests = $dom->getElementById('qunit-tests');
			$qunit_testresult = $dom->getElementById('qunit-testresult');

			if ($qunit_testresult == NULL) {
				/* happens when test timed out */
				continue;
			}

			$testresults = $qunit_testresult->getElementsByTagName('span');

			foreach ($testresults as $testresult) {
				$testresult_class = $testresult->getAttribute('class');
				if ($testresult_class == "passed")
					$qunit_passed = $testresult->nodeValue;
				else if ($testresult_class == "failed")
					$qunit_failed = $testresult->nodeValue;
				else if ($testresult_class == "total")
					$qunit_total = $testresult->nodeValue;
			}

			$report_qunit_banner = $report->importNode($qunit_banner, true);
			$report_qunit_banner = $body->appendChild($report_qunit_banner);

			$report_qunit_useragent = $report->importNode($qunit_useragent, true);
			$report_qunit_useragent = $body->appendChild($report_qunit_useragent);

			$report_qunit_tests = $report->importNode($qunit_tests, true);
			$report_qunit_tests = $body->appendChild($report_qunit_tests);

			$report_qunit_testresult = $report->importNode($qunit_testresult, true);
			$report_qunit_testresult = $body->appendChild($report_qunit_testresult);
		}

		return $report;
	}

	function generateRunReport($run_id, $view)
	{
		$result = mysql_queryf("SELECT job_id,runs.name,jobs.name,jobs.created FROM runs,jobs WHERE runs.job_id=jobs.id AND runs.id=%s LIMIT 1", $run_id);

		if ( $row = mysql_fetch_array($result) ) {
			$job_id = $row[0];
			$test_suite = $row[1];
			$job_name = $row[2];
			$creation_time = $row[3];
		}

		$result = mysql_queryf("SELECT results FROM run_client WHERE run_id=%s", $run_id);

		$dtd_string = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n" .
			"\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";

		$dom_implementation = new DOMImplementation();
		$dtd = $dom_implementation->createDocumentType('html',
			'-//W3C//DTD XHTML 1.0 Strict//EN',
			'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd');

		$report = $dom_implementation->createDocument('', '', $dtd);

		$html = $report->createElement('html');
		$html = $report->appendChild($html);

		$head = $report->createElement('head');
		$head = $report->appendChild($head);

		$title = $report->createElement('title');
		$title->nodeValue = "$job_name";
		$title = $head->appendChild($title);

		$resource_path = "";

		if ($view)
			$resource_path = "../";

		$qunit_css = $report->createElement('link');
		$qunit_css->setAttribute('rel', 'stylesheet');
		$qunit_css->setAttribute('href', $resource_path . 'css/qunit.css');
		$qunit_css->setAttribute('type', 'text/css');
		$qunit_css->setAttribute('media', 'screen');
		$qunit_css = $head->appendChild($qunit_css);

		$jquery_js = $report->createElement('script');
		$jquery_js->setAttribute('type', 'text/javascript');
		$jquery_js->setAttribute('src', $resource_path . 'js/jquery.js');
		$jquery_js = $head->appendChild($jquery_js);

		$report_js = $report->createElement('script');
		$report_js->setAttribute('type', 'text/javascript');
		$report_js->setAttribute('src', $resource_path . 'js/report.js');
		$report_js = $head->appendChild($report_js);

		$ready_js = $report->createElement('script');
		$ready_js->setAttribute('type', 'text/javascript');
		$ready_js->nodeValue = "$(document).ready(function(){initQunitReport();});";
		$ready_js = $head->appendChild($ready_js);

		$body = $report->createElement('body');
		$body = $report->appendChild($body);

		$report_qunit_header = $report->createElement('h1');
		$report_qunit_header->setAttribute('id', 'qunit-header');
		$report_qunit_header->nodeValue = "QUnit Test Report: $job_name";
		$report_qunit_header = $body->appendChild($report_qunit_header);

		while ( $row = mysql_fetch_array($result) )
		{
			/* prepend a correct DTD otherwise the PHP DOM parser won't work correctly */
			$report_html = $dtd_string . gzdecode($row[0]);

			$dom = new domDocument;
			$dom->loadHTML($report_html);
			$dom->preserveWhiteSpace = false;

			$qunit_header = $dom->getElementById('qunit-header');
			$qunit_banner = $dom->getElementById('qunit-banner');
			$qunit_useragent = $dom->getElementById('qunit-userAgent');
			$qunit_tests = $dom->getElementById('qunit-tests');
			$qunit_testresult = $dom->getElementById('qunit-testresult');

			if ($qunit_testresult == NULL) {
				/* happens when test timed out */
				continue;
			}

			$testresults = $qunit_testresult->getElementsByTagName('span');

			foreach ($testresults as $testresult) {
				$testresult_class = $testresult->getAttribute('class');
				if ($testresult_class == "passed")
					$qunit_passed = $testresult->nodeValue;
				else if ($testresult_class == "failed")
					$qunit_failed = $testresult->nodeValue;
				else if ($testresult_class == "total")
					$qunit_total = $testresult->nodeValue;
			}

			$report_qunit_banner = $report->importNode($qunit_banner, true);
			$report_qunit_banner = $body->appendChild($report_qunit_banner);

			$report_qunit_useragent = $report->importNode($qunit_useragent, true);
			$report_qunit_useragent = $body->appendChild($report_qunit_useragent);

			$report_qunit_tests = $report->importNode($qunit_tests, true);
			$report_qunit_tests = $body->appendChild($report_qunit_tests);

			$report_qunit_testresult = $report->importNode($qunit_testresult, true);
			$report_qunit_testresult = $body->appendChild($report_qunit_testresult);
		}

		return $report;
	}

	function downloadRunResults($run_id, $view)
	{
		$result = mysql_queryf("SELECT job_id,runs.name,jobs.name,jobs.created FROM runs,jobs WHERE runs.job_id=jobs.id AND runs.id=%s LIMIT 1", $run_id);

		if ( $row = mysql_fetch_array($result) ) {
			$job_id = $row[0];
			$test_suite = $row[1];
			$job_name = $row[2];
			$creation_time = $row[3];
		}

		if ($view)
		{
			$report = generateRunReport($run_id, $view);
			echo $report->saveHTML();
		}
		else
		{
			$temp_file = tempnam(sys_get_temp_dir(), 'ts');

			$zip = new ZipArchive;
			$archive = $zip->open($temp_file, ZipArchive::OVERWRITE);

			if ($archive !== TRUE)
				return;

			$report = generateRunReport($run_id, $view);
			$zip->addFromString("test-report.html", $report->saveHTML());
			$zip->addEmptyDir('/css');
			$zip->addFile('../css/qunit.css', '/css/qunit.css');
			$zip->addEmptyDir('/js');
			$zip->addFile('../js/jquery.js', '/js/jquery.js');
			$zip->addFile('../js/report.js', '/js/report.js');
			$zip->close();

			$zip_filename = "test-report-$job_name.zip";
			header('Content-Type: application/octet-stream');
			header("Content-Disposition: attachment; filename=\"$zip_filename\"");
			header('Content-Transfer-Encoding: binary');
			readfile($temp_file);
			unlink($temp_file);
		}
	}

	function downloadJobResults($run_id)
	{
		$result = mysql_queryf("SELECT job_id,runs.name,jobs.name,jobs.created FROM runs,jobs WHERE runs.job_id=jobs.id AND runs.id=%s LIMIT 1", $run_id);

		if ( $row = mysql_fetch_array($result) ) {
			$job_id = $row[0];
			$test_suite = $row[1];
			$job_name = $row[2];
			$creation_time = $row[3];
		}

		$result = mysql_queryf("SELECT id FROM runs WHERE job_id=%s", $job_id);

		$temp_file = tempnam(sys_get_temp_dir(), 'ts');

		$zip = new ZipArchive;
		$archive = $zip->open($temp_file, ZipArchive::OVERWRITE);

		if ($archive !== TRUE)
			return;

		while ( $row = mysql_fetch_array($result) )
		{
			$run_id = $row[0];
			$r = mysql_queryf("SELECT name FROM runs WHERE id=%s", $run_id);

			$i = 0;
			if ($d = mysql_fetch_array($r)) {
				$run_name = "run_name" . $i++;
				//$run_name = preg_replace('/UT: /', '', $run_name);
				//$run_name = preg_replace('/ /', '-', $run_name);
				$report = generateTestReport($run_id, false);
				$zip->addFromString($run_name . ".html", $report->saveHTML());
			}
		}

		$zip->addEmptyDir('/css');
		$zip->addFile('../css/qunit.css', '/css/qunit.css');
		$zip->addEmptyDir('/js');
		$zip->addFile('../js/jquery.js', '/js/jquery.js');
		$zip->addFile('../js/report.js', '/js/report.js');
		$zip->close();

		$zip_filename = "test-report-$job_name.zip";
		header('Content-Type: application/octet-stream');
		header("Content-Disposition: attachment; filename=\"$zip_filename\"");
		header('Content-Transfer-Encoding: binary');
		readfile($temp_file);
		unlink($temp_file);
	}
?>
