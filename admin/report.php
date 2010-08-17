<?php
	require "../logic/jobstatus.php";

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

		$site_css = $report->createElement('link');
		$site_css->setAttribute('rel', 'stylesheet');
		$site_css->setAttribute('href', $resource_path . 'css/site.css');
		$site_css->setAttribute('type', 'text/css');
		$site_css->setAttribute('media', 'screen');
		$site_css = $head->appendChild($site_css);

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
		$body->setAttribute('style', 'padding: 10px;');

		$report_qunit_header = $report->createElement('h1');
		$report_qunit_header->setAttribute('id', 'qunit-header');
		$report_qunit_header_span = $report->createElement('span');
		$report_qunit_header_span->setAttribute('id', 'report_top');
		$report_qunit_header_span->nodeValue = "QUnit Test Report: $job_name";
		$report_qunit_header->appendChild($report_qunit_header_span);
		$report_qunit_header = $body->appendChild($report_qunit_header);

		$results_table_html =
			'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" dir="ltr">' .
			'<head></head><body><div id="main">' .
			get_results_table($browsers, $runs, $job_id) .
			'</div></body></html>';

		$results = new domDocument;
		$results->loadHTML($results_table_html);
		$results->preserveWhiteSpace = false;

		$results_table = "";
		$results_div = $results->getElementsByTagName('div');

		foreach ($results_div as $div) {
			$div_class = $div->getAttribute('class');
			if ($div_class == "result-table")
				$results_table = $div;
		}

		$report_results_table = $report->importNode($results_table, true);

		$report_results_table->setAttribute('style',
				'padding: 20px;' .
				'background-color: #0D3349; ' .
				'border-bottom-left-radius: 15px; ' .
				'border-bottom-right-radius: 15px; ');

		$result_table = "";
		$tables = $report_results_table->getElementsByTagName('table');

		foreach ($tables as $table) {
			$table_class = $table->getAttribute('class');
			if ($table_class == "results")
				$result_table = $table;
		}

		$result_table->setAttribute('style',
				'padding: 20px;' .
				'background-color: #D2E0E6; ' .
				'border-top-left-radius: 15px; ' .
				'border-top-right-radius: 15px; ' .
				'border-bottom-left-radius: 15px; ' .
				'border-bottom-right-radius: 15px; ');

		if ($view < 1) {

			$icons = $result_table->getElementsByTagName('img');

			foreach ($icons as $icon) {
				$icon_src = $icon->getAttribute('src');
				$icon_src = preg_replace('/(.*images\/)(.*)/',
					$resource_path . 'images/$2', $icon_src);
				$icon->setAttribute('src', $icon_src);
			}
		}

		$table_headers = $result_table->getElementsByTagName('th');

		foreach ($table_headers as $table_header) {
			$table_headers_a = $table_header->getElementsByTagName('a');

			foreach ($table_headers_a as $table_header_a) {
				$table_header_a_href = $table_header_a->getAttribute('href');
				$table_header_a_href = preg_replace('/(.*\/)(.*)\.(.*)/', '#$2', $table_header_a_href);
				$table_header_a->setAttribute('href', $table_header_a_href);
			}
		}

		$table_cells = $result_table->getElementsByTagName('td');

		foreach ($table_cells as $table_cell) {
			$table_cells_a = $table_cell->getElementsByTagName('a');

			foreach ($table_cells_a as $table_cell_a) {
				$table_cell_a_href = $table_cell_a->getAttribute('href');
				$table_cell_a_href =
					preg_replace('/.*run_id=(\d+)&client_id=(\d+)/', '#r$1c$2', $table_cell_a_href);
				$table_cell_a->setAttribute('href', $table_cell_a_href);
			}
		}

		$report_results_table = $body->appendChild($report_results_table);

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
			$result = mysql_queryf("SELECT results,useragents.engine,os,useragents.name,url,runs.id,clients.id FROM run_client,clients,useragents,runs,jobs WHERE (run_client.client_id=clients.id AND clients.useragent_id=useragents.id AND run_client.run_id=runs.id AND runs.job_id=jobs.id AND runs.id=%s)", $run_id);

			while ( $row = mysql_fetch_array($result) )
			{
				/* prepend a correct DTD otherwise the PHP DOM parser won't work correctly */
				$report_html = $dtd_string . gzdecode($row[0]);
				$report_html = preg_replace('/<html>/',
					'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" dir="ltr">', $report_html);

				$client_id = $row[6];
				$run_id = $row[5];
				$test_url = $row[4];
				$browser_name = $row[3];
				$os = $os_name[$row[2]];
				$os_shortname = $row[2];
				$engine = $row[1];
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
					$report_qunit_header_text = $report_qunit_header->nodeValue;
					$report_qunit_header->nodeValue = "";
					$report_qunit_header_a = $report->createElement('a');
					$report_qunit_header_a_anchor =
						preg_replace('/(.*\/)(.*)\.(.*)/', '$2', $test_url);
					$report_qunit_header_a->setAttribute('name', $report_qunit_header_a_anchor);
					$report_qunit_header_a->setAttribute('href', "#report_top");
					$report_qunit_header_a->setAttribute('title', "go back to top");
					$report_qunit_header_a->nodeValue = $report_qunit_header_text;
					$report_qunit_header->appendChild($report_qunit_header_a);
					$report_qunit_header = $body->appendChild($report_qunit_header);
					$last_run_name = $run_name;
				}

				$report_qunit_banner = $report->importNode($qunit_banner, true);
				$report_qunit_banner = $body->appendChild($report_qunit_banner);

				$report_qunit_useragent = $report->importNode($qunit_useragent, true);
				$report_qunit_useragent_browser_icon = $report->createElement('img');
				$report_qunit_useragent_browser_icon->setAttribute('style',
						'width: 16px; height: 16px;' .
						'padding-top: 0px; padding-bottom: 0px;' .
						'padding-left: 0px; padding-right: 6px;');
				$report_qunit_useragent_browser_icon->setAttribute('src', $resource_path . 'images/' . $engine .'.sm.png');
				$report_qunit_useragent_os_icon = $report->createElement('img');
				$report_qunit_useragent_os_icon->setAttribute('style',
						'width: 16px; height: 16px;' .
						'padding-top: 0px; padding-bottom: 0px;' .
						'padding-left: 0px; padding-right: 6px;');
				$report_qunit_useragent_os_icon->setAttribute('src', $resource_path . 'images/os/' . $os_shortname .'.sm.png');
				$report_qunit_useragent_span = $report->createElement('span');
				$report_qunit_useragent_span->nodeValue =
						"$browser_name / $os - useragent: " . $report_qunit_useragent->nodeValue;
				$report_qunit_useragent_span->setAttribute('id', "r" . $run_id . "c" . $client_id);
				$report_qunit_useragent_span->setAttribute('title', 'click to expand / collapse');
				$report_qunit_useragent->nodeValue = "";
				$report_qunit_useragent->appendChild($report_qunit_useragent_browser_icon);
				$report_qunit_useragent->appendChild($report_qunit_useragent_os_icon);
				$report_qunit_useragent->appendChild($report_qunit_useragent_span);
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
			$zip->addFile('../css/site.css', '/css/site.css');
			$zip->addEmptyDir('/js');
			$zip->addFile('../js/jquery.js', '/js/jquery.js');
			$zip->addFile('../js/report.js', '/js/report.js');
			$zip->addEmptyDir('/images');
			$zip->addFile('../images/android.sm.png', '/images/android.sm.png');
			$zip->addFile('../images/chrome.sm.png', '/images/chrome.sm.png');
			$zip->addFile('../images/blackberry.sm.png', '/images/blackberry.sm.png');
			$zip->addFile('../images/fennec.sm.png', '/images/fennec.sm.png');
			$zip->addFile('../images/gecko.sm.png', '/images/gecko.sm.png');
			$zip->addFile('../images/konqueror.sm.png', '/images/konqueror.sm.png');
			$zip->addFile('../images/mobilewebkit.sm.png', '/images/mobilewebkit.sm.png');
			$zip->addFile('../images/msie.sm.png', '/images/msie.sm.png');
			$zip->addFile('../images/operamobile.sm.png', '/images/operamobile.sm.png');
			$zip->addFile('../images/presto.sm.png', '/images/presto.sm.png');
			$zip->addFile('../images/webkit.sm.png', '/images/webkit.sm.png');
			$zip->addFile('../images/winmo.sm.png', '/images/winmo.sm.png');
			$zip->addEmptyDir('/images/os');
			$zip->addFile('../images/os/android.sm.png', '/images/os/android.sm.png');
			$zip->addFile('../images/os/any.sm.png', '/images/os/any.sm.png');
			$zip->addFile('../images/os/ipad.sm.png', '/images/os/ipad.sm.png');
			$zip->addFile('../images/os/ipad.sm.png', '/images/os/iphone.sm.png');
			$zip->addFile('../images/os/linux.sm.png', '/images/os/linux.sm.png');
			$zip->addFile('../images/os/osx10.5.sm.png', '/images/os/osx10.5.sm.png');
			$zip->addFile('../images/os/osx10.6.sm.png', '/images/os/osx10.6.sm.png');
			$zip->addFile('../images/os/osx.sm.png', '/images/os/osx.sm.png');
			$zip->addFile('../images/os/win7.sm.png', '/images/os/win7.sm.png');
			$zip->addFile('../images/os/xp.sm.png', '/images/os/xp.sm.png');
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

	function downloadJobStatus($job_id, $view)
	{
		echo '<html><head>';
		echo '<link rel="stylesheet" href="../css/site.css">';
		echo '<script type="text/javascript" src="../js/jquery.js"></script>';
		echo '<script type="text/javascript" src="../js/status.js"></script>';
		echo '</head><body><div id="main">';
		echo get_results_table($browsers, $runs, $job_id);
		echo '</body></div></html>';
	}
?>
