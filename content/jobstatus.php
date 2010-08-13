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

	$result = mysql_queryf("SELECT jobs.name, jobs.status, users.name as user_name FROM jobs, users WHERE jobs.id=%u AND users.id=jobs.user_id;", $job_id);

	if ( $row = mysql_fetch_array($result) ) {
		$job_name = $row[0];
		$job_status = get_status(intval($row[1]));
		$owner = ($row[2] == $_SESSION['username']);
	}
	
	function generate_browser_header_row(&$browsers){
	  $header = "<tr><th></th>\n";
		foreach ( $browsers as $browser => $oses ) {
		  ksort($oses);
		  foreach ($oses as $os => $browser) {
				$header .= '<th>' . '<div class="browser ' . $browser["engine"] . ' ' . $browser["os"] . '">' .
					'<img src="' . $GLOBALS['contextpath'] . '/images/' . $browser["engine"] .
					'.sm.png" class="browser-icon ' . $browser["engine"] .
					'" alt="' . $browser["name"] .
					'" title="' . $browser["name"] . ' (' . $browser["os"] . ')' .
					'"/><span class="browser-name">' .
					preg_replace('/\w+ /', "", $browser["name"]) . ', ' .
					"</span></div><div class='browser-os'>" . 
					'<img src="' . $GLOBALS['contextpath'] . '/images/os/' . $browser["os"] .
						'.sm.png"' .
						'" alt="' . $browser["os"] .
						'" title="' . $browser["name"] . ' (' . $browser["os"] . ')' . '"' .
						"</div></th>\n";
			}
			$last_browser = $browser;
		}
		return $header;
	}
	
	function add_browser_info(&$browsers, $name, $engine, $id, $os) {
	  // register the new browser config, if it's not already registered
	  if (! $os || $os == '') {
	    $os = 'unknown';
	  }
		$browser_info = array(
			"name" => $name,
			"engine" => $engine,
			"id" => $id,
			"os" => $os
		);
    if (! array_key_exists($name, $browsers)) {
      $browsers[$name] = array();
      $browsers[$name][$os] = $browser_info;
	  } else {
	    if ($os == "any") {
	      // we already have a result for this useragent, so we don't need to add the default value
	      return;
	    }
	    if (! array_key_exists($os, $browsers[$name])) {
        $browsers[$name][$os] = $browser_info;
  	  }
	  }
	}
	
	function setup_useragents_for_run($run_id, &$browsers) {
	  $useragents = array();

		$runResult = mysql_queryf("SELECT run_client.client_id as client_id, run_client.status as status, run_client.fail as fail, run_client.error as error, run_client.total as total, clients.useragent_id as useragent_id, useragents.engine as browser, useragents.name as browsername, clients.os as os FROM run_client, clients, useragents WHERE run_client.run_id=%u AND run_client.client_id=clients.id AND clients.useragent_id=useragents.id ORDER BY useragent_id, os;", $run_id);

		while ( $ua_row = mysql_fetch_assoc($runResult) ) {
		  $key = $ua_row['useragent_id'] . '--' . ($ua_row['os'] == '' ? 'unknown' : $ua_row['os']);
			if ( ! array_key_exists($key, $useragents) ) {
				$useragents[ $key ] = array();
				add_browser_info($browsers, $ua_row["browsername"], $ua_row["browser"], $ua_row["useragent_id"], $ua_row["os"]);
			}

			array_push( $useragents[ $key ], $ua_row );
		}
		
		$additionalBrowsers = mysql_queryf("SELECT useragents.* FROM useragents, run_useragent, runs WHERE runs.id=%u AND runs.id=run_useragent.run_id AND run_useragent.useragent_id=useragents.id", $run_id);
		while ( $row = mysql_fetch_assoc($additionalBrowsers)) {
		  add_browser_info($browsers, $row["name"], $row["engine"], $row["id"], "any");
		}
		
		return $useragents;
	}
	
	function get_useragent_results($uas, $row, $browser, $max) {
	  $out = '<td class="results ' . $browser["name"] . ' ' . $browser["os"] . '">';
	  $count = count($uas);
	  $result_size = $max/$count;
	  if ($max == 1) {
	    $result_size = 1.7;
	  }
	  foreach ($uas as $ua) {
	    
  	  $status = get_status2(intval($ua["status"]), intval($ua["fail"]), intval($ua["error"]), intval($ua["total"]));
  		$out .= "<div class='$status " . $browser["name"] . ' ' . $browser["os"] . "' style='height: " . $result_size . "em;'><a href='" . $GLOBALS['contextpath'] . "/?state=runresults&run_id=" . $row["run_id"] . "&client_id=" . $ua["client_id"] . "'>" .
  			($ua["status"] == 2 ?
  				($ua["total"] < 0 ?
  					"0" :
  					($ua["error"] > 0 ?
  						$ua["error"] :
  						($ua["fail"] > 0 ?
  							$ua["fail"] :
  							$ua["total"])))
  				: "") . "</a></div>\n";
		}
		return $out . '</td>' . "\n";
	}
	
  function get_max_results($useragents) {
    $max = 0;
    foreach ($useragents as $agent => $results) {
      $count = count($results);
      if ($count > $max) { $max = $count; }
    }
    return $max;
  }

?>

<?php
echo '<table border="0"><tr>';
echo '<td><h3>' . $job_name . '</h3></td>';
echo '<td><a style="border:none" href="'. $GLOBALS['contextpath'] .
	'/admin/admin.php?action=report&job_id=' . $job_id . '">' .
	'<img title="download report" style="border:none" ' .
	'src="' . $GLOBALS['contextpath'] . '/images/download.png"/></a></td>';
echo '<td><a style="border:none" href="'. $GLOBALS['contextpath'] .
	'/admin/admin.php?action=report&job_id=' . $job_id . '&view=1">' .
	'<img title="download report" style="border:none" ' .
	'src="' . $GLOBALS['contextpath'] . '/images/view.png"/></a></td>';
echo '</tr></table>';
?>

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

	$result = mysql_queryf("SELECT runs.id as run_id, runs.url as run_url, runs.name as run_name FROM runs WHERE runs.job_id=%u ORDER BY run_id;", $job_id);

	$last = "";
	$output = "";
	$browsers = array();
	$runs = array();

  // cache all of the report data
	while ( $row = mysql_fetch_assoc($result) ) {
	  if (array_key_exists($row["run_id"], $runs)) {
	    // append the run info
	  } else {
	    $useragents = setup_useragents_for_run($row["run_id"], $browsers);
	    $run_data = array();
	    $run_data["useragents"] = $useragents;
	    $run_data["run_url"] = $row["run_url"];
	    $run_data["run_name"] = $row["run_name"];
	    $runs[$row["run_id"]] = $run_data;
	  }
  }
  
  ksort($browsers);

  // now print out the table
  echo generate_browser_header_row($browsers);

  foreach ($runs as $run) {
    echo "<tr><th><a href='" . $run["run_url"] . "'>" . $run["run_name"] . "</a></th>\n";
    $useragents = $run["useragents"];
    $max = get_max_results($useragents);
    foreach ($browsers as $browser => $oses) {
      ksort($oses);
      foreach ($oses as $browser) {
        $key = $browser["id"] . '--' . $browser["os"];
        if (array_key_exists($key, $useragents)) {
          $run_results = $useragents[$key];
          echo get_useragent_results($run_results, $run, $browser, $max);
        } else {
          echo "<td class='notstarted notdone " . $browser["name"] . " " . $browser["os"] . "'>&nbsp;</td>";
        }
      }
    }
    echo "</tr>";
  }

?>
</tbody>
</table>
