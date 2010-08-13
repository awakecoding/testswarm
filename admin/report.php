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

		$dtd_string = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n" .
			"\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";

		$dom_implementation = new DOMImplementation();
		$dtd = $dom_implementation->createDocumentType('html',
			'-//W3C//DTD XHTML 1.0 Strict//EN',
			'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd');

		libxml_use_internal_errors(TRUE);
		$report = $dom_implementation->createDocument('', '', $dtd);

		$html = $report->createElement('html');
		$html = $report->appendChild($html);

		$head = $report->createElement('head');
		$head = $html->appendChild($head);

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
		$body = $html->appendChild($body);

		$report_qunit_header = $report->createElement('h1');
		$report_qunit_header->setAttribute('id', 'qunit-header');
		$report_qunit_header->nodeValue = "QUnit Test Report: $job_name";
		$report_qunit_header = $body->appendChild($report_qunit_header);

		$os_name = array();
		$os_name['linux'] = "Linux";
		$os_name['win7'] = "Windows 7";
		$os_name['xp'] = "Windows XP";
		$os_name['osx'] = "Mac OS X";
		$os_name['osx10.5'] = "Mac OS X 10.5";
		$os_name['iphone'] = "iPhone 0S";
		$os_name['android'] = "Android";

		$last_run_name = "";
		$result_runs = mysql_queryf("SELECT id,name FROM runs WHERE job_id=%s", $job_id);
		while ( $row_runs = mysql_fetch_array($result_runs) )
		{
			$run_id = $row_runs[0];
			$run_name = $row_runs[1];
			//$result = mysql_queryf("SELECT results FROM run_client WHERE run_id=%s", $run_id);
			$result = mysql_queryf("SELECT results,useragents.engine,os,useragents.name FROM run_client,clients,useragents,runs,jobs WHERE (run_client.client_id=clients.id AND clients.useragent_id=useragents.id AND run_client.run_id=runs.id AND runs.job_id=jobs.id AND runs.id=%s)", $run_id);

			while ( $row = mysql_fetch_array($result) )
			{
				/* prepend a correct DTD otherwise the PHP DOM parser won't work correctly */
				$report_html = $dtd_string . gzdecode($row[0]);
				$report_html = preg_replace('/<html>/',
					'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" dir="ltr">', $report_html);

				$browser_name = $row[3];
				$os = $os_name[$row[2]];
				if (strlen($os) < 1) $os = $row[2];

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

				if ($run_name != $last_run_name) {
					$body->appendChild($report->createElement('br'));
					$report_qunit_header = $report->importNode($qunit_header, true);
					$report_qunit_header = $body->appendChild($report_qunit_header);
					$last_run_name = $run_name;
				}

				$report_qunit_banner = $report->importNode($qunit_banner, true);
				$report_qunit_banner = $body->appendChild($report_qunit_banner);

				$report_qunit_useragent = $report->importNode($qunit_useragent, true);
				$report_qunit_useragent->nodeValue = "$browser_name / $os - useragent: " . $report_qunit_useragent->nodeValue;
				$report_qunit_useragent = $body->appendChild($report_qunit_useragent);

				$report_qunit_tests = $report->importNode($qunit_tests, true);
				$report_qunit_tests = $body->appendChild($report_qunit_tests);

				$report_qunit_testresult = $report->importNode($qunit_testresult, true);
				$report_qunit_testresult = $body->appendChild($report_qunit_testresult);
			}
		}

		return $report;
	}

	function downloadJobResultsForRunId($run_id, $view)
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
			$report = generateTestReport($run_id, $view);
			echo $report->saveHTML();
		}
		else
		{
			$temp_file = tempnam(sys_get_temp_dir(), 'ts');

			$zip = new ZipArchive;
			$archive = $zip->open($temp_file, ZipArchive::OVERWRITE);

			if ($archive !== TRUE)
				return;

			$report = generateTestReport($run_id, $view);
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

	function downloadJobResults($job_id, $view)
	{
		$result = mysql_queryf("SELECT runs.id FROM runs,jobs WHERE runs.job_id=jobs.id AND jobs.id=%s LIMIT 1", $job_id);

		if ( $row = mysql_fetch_array($result) ) {
			$run_id = $row[0];
			downloadJobResultsForRunId($run_id, $view);
		}
	}
?>
