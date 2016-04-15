<?php

// for this to work, set megamud to issue a "^M" every 3 seconds

function remove_badchars($string){ 
    $ret = ""; $badchars = array(69,91,97,254);
	for ($x = 101; $x <= 117; $x++) { $badchars[] = $x;	}
	for ($x = 176; $x <= 223; $x++) { $badchars[] = $x;	}
    for ($n=0;$n<strlen($string);$n++){ 
        if (!in_array(ord($string[$n]), $badchars)) {
			$ret .= $string[$n];
			if (ord($string[$n]) != 32) {
				//echo "(".ord($string[$n]).")";
			}
		}
    } 
    return $ret; 
} 

$error = '';
$capture_data = ''; $capture_file_name = '';
if(isset($_POST["submit"]) || isset($_POST['submit_ignore_unparsed'])) {
	if ($_FILES["fileToUpload"]["error"] == 0 && is_uploaded_file($_FILES["fileToUpload"]["tmp_name"])
		&& $_FILES["fileToUpload"]["size"] < 5000000 && mime_content_type($_FILES['fileToUpload']['tmp_name']) == 'text/plain') {
		
		$capture_data = file_get_contents($_FILES['fileToUpload']['tmp_name']);
		$capture_file_name = $_FILES['fileToUpload']['name'];
		
	} elseif ($_FILES["fileToUpload"]["error"] == 0 && ($_FILES["fileToUpload"]["size"] >= 5000000 || $_FILES['fileToUpload']['type'] != 'text/plain')) {
		$error = 'File Size: '.$_FILES["fileToUpload"]["size"].', Type: '.mime_content_type($_FILES['fileToUpload']['tmp_name']);
	}
	if (is_uploaded_file($_FILES["fileToUpload"]["tmp_name"])) unlink($_FILES["fileToUpload"]["tmp_name"]); 
}

$unparsed = array();
$room_names = array();
$room_names[] = 'Newhaven';
$room_names[] = 'Temple,';

$deflect = preg_quote(', but your armour deflects the blow!', "/");
$i=0; $attacks = array(); //1=name, 2=hit msg, 3=miss/fail msg, 4=resist
$i++; $attacks[$i][1] = 'BITE'; $attacks[$i][2] = 'The SOURCE bites you for DMG damage!'; $attacks[$i][3] = 'The SOURCE lunges at you';
$i++; $attacks[$i][1] = 'CLAW'; $attacks[$i][2] = 'SOURCE claws you for DMG damage!'; $attacks[$i][3] = 'SOURCE swings at you with its huge claws!';
$i++; $attacks[$i][1] = 'LASH'; $attacks[$i][2] = 'SOURCE lashes you with its tail for DMG damage!'; $attacks[$i][3] = 'SOURCE lashes at you with its tail!';
$i++; $attacks[$i][1] = 'WHIP'; $attacks[$i][2] = 'SOURCE whips you for DMG damage!'; $attacks[$i][3] = 'SOURCE lashes out at you with their hellfire whip!';
$i++; $attacks[$i][1] = 'SLASH'; $attacks[$i][2] = 'SOURCE slashes you for DMG damage!'; $attacks[$i][3] = 'SOURCE swings at you with their SOURCE!';

$standard_spells = array();
$standard_spells['LBOL'] = "lbol";
$standard_spells['LBOLT'] = "lightning bolt";
$standard_spells['SBOLT'] = "sunbolt";
$standard_spells['MBLAST'] = "magma blast";
foreach ($standard_spells as $short_name => $long_name) {
	$i++;
	$attacks[$i][1] = $short_name;
	$attacks[$i][2] = 'SOURCE MSGTEXT '.$long_name.' at you for DMG damage!';
	$attacks[$i][3] = 'SOURCE attempted to cast '.$long_name.' at you, but failed.';
	$attacks[$i][4] = 'You resisted SOURCE cast of '.$long_name;
}

$i++; $attacks[$i][1] = 'HEAL'; $attacks[$i][2] = 'The room casts room heal on you, healing DMG damage!'; $attacks[$i][3] = 'AAAAAAA';

$i++; $attacks[$i][1] = 'HSTORM'; $attacks[$i][2] = 'A hellish storm of fire and brimstone scorches you for DMG damage!';
								  $attacks[$i][3] = 'SOURCE attempted to cast hellstorm, but failed.';
								  $attacks[$i][4] = 'You resisted SOURCE cast of hellstorm';

$between_rounds = array(); $i=1;
$i++; $between_rounds[$i][0] = 'WIND'; $between_rounds[$i][1] = 'The SOURCE flaps its wings and a gust of wind knocks you to the ground!';
$i++; $between_rounds[$i][0] = 'CHAINS'; $between_rounds[$i][1] = 'Glowing silver manacles clamp around your wrists!';

$regular_line_strips = array();
function AddStrip($text_or_arr) {
	global $regular_line_strips;
	if (!is_array($text_or_arr)) $text_or_arr = array($text_or_arr);
	foreach ($text_or_arr as $text) { $regular_line_strips[] = $text; }
}

if (!empty($capture_data)) {
	$capture_data = preg_replace("/.*Obvious exits\:.*/", 'NEWROUND', $capture_data);
	
	//remove non-printable characters
	$capture_data = preg_replace( '/[^[:print:]\r\n]/', '',$capture_data);
	
	//regex strips
	$capture_data = preg_replace("/.*\\[HP\\=\\d+[a-zA-Z0-9\\(\\)\\s]*\\]\\:.*/", '', $capture_data);
	$capture_data = preg_replace("/.*[a-zA-Z0-9\\\\\\/\\:\\s]+   [a-zA-Z0-9\\\\\\/\\:\\s]+   .*/", '', $capture_data);
	
	$regex_line_strips = array();
	$regex_line_strips[] = 'TEXTVAR makes a quick grasping gesture.';
	$regex_line_strips[] = 'You gain NUMVAR experience';
	$regex_line_strips[] = 'The TEXTVAR falls to the ground.';
	$regex_line_strips[] = 'You TEXTVAR for NUMVAR damage\!';
	$regex_line_strips[] = 'You notice TEXTVAR here.';
	foreach ($regex_line_strips as $text) {
		$regex = preg_quote($text, "/");
		$regex = str_replace('TEXTVAR', '[ a-zA-Z0-9\']+', $regex);
		$regex = str_replace('NUMVAR', '\\d+', $regex);
		$capture_data = preg_replace("/.*".$regex.".*/", '', $capture_data);
	}
	
	AddStrip(array('Also here:', 'You hear movement ', ' into the room from ', ' just entered the Realm', ' just left the Realm', ' just hung up', ' peeks in from ', 'the server will be shutting', 'nightly "auto-cleanup"', 'Please finish up and log off', 'Software caused connection abort', 'Session ended', 'or type "new"', 'Enter your password', 'password you have given', 'glad to see you back', 'majorMUD', 'Please select', '...', 'Main System Menu', 'your selection', 'you have selected', 'U L T I M A T E', '**', '(C)ontinue', 'WG3NT', 'Realm Of Legends', '[', 'RECOMMENDED', 'Last time you were on', 'gods have punished', '=-=', 'You say "', 'Combat Engaged', 'Combat Off', 'Invalid Option', 'You are now resting', 'You hand over', 'wounds are healed', 'loudly,', 'in the air', 'through the air', 'sys goto', 'You are carrying', 'copper farthing', 'You have ', 'Wealth:', 'Encumbrance:', 'Top Gangs', 'scatters some ashes in a sweeping', 'You are flat on your back!', 'You get back on your feet.', 'You are restrained by silvery manacles!', 'The silvery manacles restraining you shimmer and fade.'));
	
	// extra attacks, ignoring for now
	$regular_line_strips[] = 'Hellfire burns you';
	
	foreach ($regular_line_strips as $line_strip) {
		$capture_data = preg_replace("/.*".preg_quote($line_strip, "/").".*/i", '', $capture_data);
	}
	
	foreach ($room_names as $room) {
		$capture_data = preg_replace("/.*".preg_quote($room, '/').".*/", '', $capture_data);
	}
	
	//remove blank lines
	$capture_data = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $capture_data);
	
	foreach ($between_rounds as $num => $between) {
		$capture_data = preg_replace("/.*".str_replace('SOURCE', '[ a-zA-Z0-9\']+', preg_quote($between[1], '/')).".*/", 'PARSED_'.$between[0].$num.',0,between', $capture_data);
	}
	
	foreach ($attacks as $num => $attack) {
		
		if (!empty($attack[4])) {
			$capture_data = preg_replace("/.*".str_replace('SOURCE', '[ a-zA-Z0-9\']+', preg_quote($attack[4], '/')).".*/", 'PARSED_'.$attack[1].$num.',0,resist', $capture_data);
		}
		
		$miss = str_replace('SOURCE', '[ a-zA-Z0-9\']+', preg_quote($attack[3], '/'));
		$miss = str_replace('MSGTEXT', '[ a-zA-Z\']+', $miss);
		$capture_data = preg_replace("/.*".$miss.$deflect.".*/", 'PARSED_'.$attack[1].$num.',0,deflect', $capture_data);
		$capture_data = preg_replace("/.*".$miss.".*/", 'PARSED_'.$attack[1].$num.',0,miss/fail', $capture_data);
		
		$hit = preg_quote($attack[2], '/');
		$hit = str_replace('SOURCE', '[ a-zA-Z0-9\']+', $hit);
		$hit = str_replace('DMG', '(\d+)', $hit);
		$hit = str_replace('MSGTEXT', '[ a-zA-Z\']+', $hit);
		
		$capture_data = preg_replace("/.*".$hit.".*/", 'PARSED_'.$attack[1].$num.',$1', $capture_data);
	}
	
	$captured_data_lines = split("\n", $capture_data);
	$max_index = count($captured_data_lines)-1;
	for ($x = 0; $x <= $max_index; $x++) {
		if ($captured_data_lines[$x] != 'NEWROUND' && substr($captured_data_lines[$x], 0, strlen('PARSED_')) != 'PARSED_') {
			if (isset($_POST['submit_ignore_unparsed'])) {
				unset($captured_data_lines[$x]);
			} elseif (!empty(trim(remove_badchars($captured_data_lines[$x])))) {
				//echo 'Unparsed: '.$captured_data_lines[$x];
				$unparsed[] = $captured_data_lines[$x];
			}
		} else {
			$captured_data_lines[$x] = preg_replace("/PARSED_(.*)/", '$1', $captured_data_lines[$x]);
		}
	}
	$capture_data = implode("\r\n", $captured_data_lines);
	
	if (empty($unparsed) && !empty($capture_data)) {
		//remove double NEWROUND lines
		while (preg_match('/NEWROUND..?NEWROUND/s', $capture_data)) {
			$capture_data = preg_replace("/NEWROUND..?NEWROUND/s", 'NEWROUND', $capture_data);
		}
		
		//move healing only rounds into the previous round
		while (preg_match("/(?:NEWROUND..?((?:HEAL[a-zA-Z0-9\\,]+..?)+))NEWROUND/s", $capture_data)) {
			$capture_data = preg_replace("/(?:NEWROUND..?((?:HEAL[a-zA-Z0-9\\,]+..?)+))NEWROUND/s", '$1NEWROUND', $capture_data);
		}
		
		//add round numbers, remove "newround"
		$round = 1; $first_lines_in_round = true;
		$captured_data_lines = split("\r\n", trim($capture_data));
		$max_index = count($captured_data_lines)-1;
		for ($x = 0; $x <= $max_index; $x++) {
			if (trim($captured_data_lines[$x]) == "NEWROUND") {
				unset($captured_data_lines[$x]);
				if ($x > 0) $round++;
				$first_lines_in_round = true; continue;
			}
			$captured_data_lines[$x] = trim($captured_data_lines[$x]);
			if (!empty($captured_data_lines[$x])) {
				//put heals at end of rounds and not at beginning of next
				if ($first_lines_in_round && substr($captured_data_lines[$x], 0, strlen('HEAL')) == 'HEAL') {
					$captured_data_lines[$x] = ($round-1).','.$captured_data_lines[$x];
				} else {
					$captured_data_lines[$x] = $round.','.$captured_data_lines[$x];
					$first_lines_in_round = FALSE;
				}
			}
		}
		$capture_data = implode("\r\n", $captured_data_lines);
		$capture_data = "Round,Attack,DMG,Note\r\n".$capture_data;
		
		header('Content-Disposition: attachment; filename="'.str_ireplace(array('.txt','.cap'), '', $capture_file_name).'.csv"');
		header('Content-Type: text/plain'); # Don't use application/force-download - it's not a real MIME type, and the Content-Disposition header is sufficient
		header('Content-Length: ' . strlen($capture_data));
		header('Connection: close');
		echo $capture_data;
		exit;
	}
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Capture Parse</title>
</head>
<body>
<?php if (!empty($unparsed)): ?>
<pre>
Unparsed Lines (<?php echo count($unparsed); ?>):
<?php echo implode("\r\n", $unparsed); ?>
</pre>
<?php elseif (!empty($error)): ?>
File Upload Error: <?php echo $error; ?>
<?php endif; ?>
<form action="capture_parse.php" method="post" enctype="multipart/form-data" name="form1" id="form1">
<p>
  <label for="fileToUpload">Capture:</label>
  <input type="file" name="fileToUpload" id="fileToUpload">
</p>
  <p>
    <input type="submit" name="submit" id="submit" value="Upload"><br>
    <br><input type="submit" name="submit_ignore_unparsed" id="submit_ignore_unparsed" value="Upload and remove unparsed lines.">
  </p>
</form>
</body>
</html>
