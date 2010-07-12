<?php

	function get_status($num){
		if ( $num == 0 ) {
			return "Not started yet.";
		} else if ( $num == 1 ) {
			return "In progress.";
		} else {
			return "Completed.";
		}
	}

	function get_status2($num, $fail, $error, $total){
		if ( $num == 0 ) {
			return "notstarted notdone";
		} else if ( $num == 1 ) {
			return "progress notdone";
		} else if ( $num == 2 && $fail == -1 ) {
			return "timeout";
		} else if ( $num == 2 && ($error > 0 || $total == 0) ) {
			return "error";
		} else {
			return $fail > 0 ? "fail" : "pass";
		}
	}

	$job_id = preg_replace("/[^0-9]/", "", $_REQUEST['job_id']);

	$result = mysql_queryf("SELECT jobs.name, jobs.status, users.name FROM jobs, users WHERE jobs.id=%u AND users.id=jobs.user_id;", $job_id);

	if ( $row = mysql_fetch_array($result) ) {
		$job_name = $row[0];
		$job_status = get_status(intval($row[1]));
		$owner = ($row[2] == $_SESSION['username']);
	}
	
	function generate_browser_header_row($browsers){
	  $header = "<tr><th></th>\n";
		$last_browser = array();
		foreach ( $browsers as $browser ) {
			if ( ! array_key_exists("id", $last_browser) || $last_browser["id"] != $browser["id"] ) {
				$header .= '<th><div class="browser">' .
					'<img src="' . $GLOBALS['contextpath'] . '/images/' . $browser["engine"] .
					'.sm.png" class="browser-icon ' . $browser["engine"] .
					'" alt="' . $browser["name"] .
					'" title="' . $browser["name"] .
					'"/><span class="browser-name">' .
					preg_replace('/\w+ /', "", $browser["name"]) . ', ' .
					"</span></div></th>\n";
			}
			$last_browser = $browser;
		}
		$header .= "</tr>\n";
		return $header;
	}
	
	function setup_useragents_for_run($run_id) {
	  $useragents = array();

		$runResult = mysql_queryf("SELECT run_client.client_id as client_id, run_client.status as status, run_client.fail as fail, run_client.error as error, run_client.total as total, clients.useragent_id as useragent_id FROM run_client, clients WHERE run_client.run_id=%u AND run_client.client_id=clients.id ORDER BY useragent_id;", $run_id);

		while ( $ua_row = mysql_fetch_assoc($runResult) ) {
			if ( ! array_key_exists($ua_row['useragent_id'], $useragents) ) {
				$useragents[ $ua_row['useragent_id'] ] = array();
			}

			array_push( $useragents[ $ua_row['useragent_id'] ], $ua_row );
		}
		return $useragents;
	}
	
	function get_useragent_results($ua, $row) {
	  $status = get_status2(intval($ua["status"]), intval($ua["fail"]), intval($ua["error"]), intval($ua["total"]));
		return "<td class='$status " . $row["browser"] . "'><a href='" . $GLOBALS['contextpath'] . "/?state=runresults&run_id=" . $row["run_id"] . "&client_id=" . $ua["client_id"] . "'>" .
			($ua["status"] == 2 ?
				($ua["total"] < 0 ?
					"Err" :
					($ua["error"] > 0 ?
						$ua["error"] :
						($ua["fail"] > 0 ?
							$ua["fail"] :
							$ua["total"])))
				: "") . "</a></td>\n";
	}

?>

<h3><?php echo $job_name; ?></h3>

<?php if ( $owner && $_SESSION['auth'] == 'yes' ) { ?>
<form action="<?php echo $GLOBALS['contextpath']; ?>/" method="POST">
	<input type="hidden" name="state" value="wipejob"/>
	<input type="hidden" name="job_id" value="<?php echo $job_id; ?>"/>
	<input type="submit" name="type" value="delete"/>
	<input type="submit" name="type" value="reset"/>
</form>
<?php } ?>

<table class="results"><tbody>
<?php

	$result = mysql_queryf("SELECT runs.id as run_id, runs.url as run_url, runs.name as run_name, useragents.engine as browser, useragents.name as browsername, useragents.id as useragent_id, run_useragent.status as status FROM run_useragent, runs, useragents WHERE runs.job_id=%u AND run_useragent.run_id=runs.id AND run_useragent.useragent_id=useragents.id ORDER BY run_id, browsername;", $job_id);

	$last = "";
	$output = "";
	$browsers = array();

	while ( $row = mysql_fetch_assoc($result) ) {
	  // if we're on to a new run, set up the useragents and start up a new run row
		if ( $row["run_id"] != $last ) {
		  // if we're not just on the first row, close out the last run row
		  if ($output != "") {
		    $output .= "</tr>\n";
		  }
      $useragents = setup_useragents_for_run($row["run_id"]);
			$output .= '<tr><th><a href="' . $row["run_url"] . '">' . $row["run_name"] . "</a></th>\n";
		}

    // register the browser if it hasn't already been
    $browser_info = array(
			"name" => $row["browsername"],
			"engine" => $row["browser"],
			"id" => $row["useragent_id"]
		);
		
    if (! in_array($browser_info, $browsers)) {
		  array_push( $browsers, $browser_info );
	  }

		#echo "<li>" . $row["browser"] . " (" . get_status(intval($row["status"])) . ")<ul>";

		$last_browser = -1;
		if ( array_key_exists($row["useragent_id"], $useragents) ) {
		  // this gets the first result sent back per user agent...
		  // ???: Should we represent the other results? If so, how?
			foreach ( $useragents[ $row["useragent_id"] ] as $ua ) {
				if ( $last_browser != $ua["useragent_id"] ) {
          $output .= get_useragent_results($ua, $row);
				}
				$last_browser = $ua["useragent_id"];
			}
		} else {
			$output .= "<td class='notstarted notdone'>&nbsp;</td>\n";
		}

		#echo "</ul></li>";

		$last = $row["run_id"];
	}
	
	// finally generate the browser icon header row
	$header = generate_browser_header_row($browsers);
	$output = $header . $output;

  // close out the final run
	$output .= "</tr>\n";

	echo "$output</tbody>\n</table>";
?>
