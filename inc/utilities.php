<?php
	# Handy function from:
	# http://us2.php.net/manual/en/function.stripslashes.php#79976
	if (get_magic_quotes_gpc()) {
		function stripslashes_deep($value) {
			$value = is_array($value) ?
				array_map('stripslashes_deep', $value) :
				stripslashes($value);

			return $value;
		}

		$_POST = array_map('stripslashes_deep', $_POST);
		$_GET = array_map('stripslashes_deep', $_GET);
		$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
		$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
	}

	# Handy function from:
	# http://us.php.net/manual/en/function.mysql-query.php#86447
	function mysql_queryf($string) {
		global $DEBUG_ON;

		$args = func_get_args();
		array_shift($args);
		$len = strlen($string);
		$sql_query = "";
		$args_i = 0;
		for($i = 0; $i < $len; $i++) {
			if($string[$i] == "%") {
				$char = $string[$i + 1];
				$i++;
				switch($char) {
					case "%":
						$sql_query .= $char;
						break;
					case "u":
						$sql_query .= "'" . intval($args[$args_i]) . "'";
						break;
					case "s":
						$sql_query .= "'" . mysql_real_escape_string($args[$args_i]) . "'";
						break;
					case "x":
						$sql_query .= "'" . dechex($args[$args_i]) . "'";
						break;
				}
				if($char != "x") {
					$args_i++;
				}
			} else {
				$sql_query .= $string[$i];
			}
		}
		if ( $DEBUG_ON ) {
			echo "$sql_query<br>\n";
		}
		$result = mysql_query($sql_query);
		if (!$result) {
		    die('Invalid query: ' . mysql_error());
		}
		return $result;
	}

	/* code snippet taken from http://www.php.net/manual/en/function.gzdecode.php */
	function gzdecode($data,&$filename='',&$error='',$maxlength=null)
	{
	    $len = strlen($data);
	    if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
		$error = "Not in GZIP format.";
		return null;  // Not GZIP format (See RFC 1952)
	    }
	    $method = ord(substr($data,2,1));  // Compression method
	    $flags  = ord(substr($data,3,1));  // Flags
	    if ($flags & 31 != $flags) {
		$error = "Reserved bits not allowed.";
		return null;
	    }
	    // NOTE: $mtime may be negative (PHP integer limitations)
	    $mtime = unpack("V", substr($data,4,4));
	    $mtime = $mtime[1];
	    $xfl   = substr($data,8,1);
	    $os    = substr($data,8,1);
	    $headerlen = 10;
	    $extralen  = 0;
	    $extra     = "";
	    if ($flags & 4) {
		// 2-byte length prefixed EXTRA data in header
		if ($len - $headerlen - 2 < 8) {
		    return false;  // invalid
		}
		$extralen = unpack("v",substr($data,8,2));
		$extralen = $extralen[1];
		if ($len - $headerlen - 2 - $extralen < 8) {
		    return false;  // invalid
		}
		$extra = substr($data,10,$extralen);
		$headerlen += 2 + $extralen;
	    }
	    $filenamelen = 0;
	    $filename = "";
	    if ($flags & 8) {
		// C-style string
		if ($len - $headerlen - 1 < 8) {
		    return false; // invalid
		}
		$filenamelen = strpos(substr($data,$headerlen),chr(0));
		if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
		    return false; // invalid
		}
		$filename = substr($data,$headerlen,$filenamelen);
		$headerlen += $filenamelen + 1;
	    }
	    $commentlen = 0;
	    $comment = "";
	    if ($flags & 16) {
		// C-style string COMMENT data in header
		if ($len - $headerlen - 1 < 8) {
		    return false;    // invalid
		}
		$commentlen = strpos(substr($data,$headerlen),chr(0));
		if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
		    return false;    // Invalid header format
		}
		$comment = substr($data,$headerlen,$commentlen);
		$headerlen += $commentlen + 1;
	    }
	    $headercrc = "";
	    if ($flags & 2) {
		// 2-bytes (lowest order) of CRC32 on header present
		if ($len - $headerlen - 2 < 8) {
		    return false;    // invalid
		}
		$calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
		$headercrc = unpack("v", substr($data,$headerlen,2));
		$headercrc = $headercrc[1];
		if ($headercrc != $calccrc) {
		    $error = "Header checksum failed.";
		    return false;    // Bad header CRC
		}
		$headerlen += 2;
	    }
	    // GZIP FOOTER
	    $datacrc = unpack("V",substr($data,-8,4));
	    $datacrc = sprintf('%u',$datacrc[1] & 0xFFFFFFFF);
	    $isize = unpack("V",substr($data,-4));
	    $isize = $isize[1];
	    // decompression:
	    $bodylen = $len-$headerlen-8;
	    if ($bodylen < 1) {
		// IMPLEMENTATION BUG!
		return null;
	    }
	    $body = substr($data,$headerlen,$bodylen);
	    $data = "";
	    if ($bodylen > 0) {
		switch ($method) {
		case 8:
		    // Currently the only supported compression method:
		    $data = gzinflate($body,$maxlength);
		    break;
		default:
		    $error = "Unknown compression method.";
		    return false;
		}
	    }  // zero-byte body content is allowed
	    // Verifiy CRC32
	    $crc   = sprintf("%u",crc32($data));
	    $crcOK = $crc == $datacrc;
	    $lenOK = $isize == strlen($data);
	    if (!$lenOK || !$crcOK) {
		$error = ( $lenOK ? '' : 'Length check FAILED. ') . ( $crcOK ? '' : 'Checksum FAILED.');
		return false;
	    }
	    return $data;
	}
?>
