<?php

function p($left,$right) {
	echo $left;
	if (!empty($right)) echo str_repeat(' ', 20-strlen(strip_tags($left))).$right;
	echo "<br>";
}

function damage($d) {
	return '<span style="color:#FD0004;">'.$d.'</span>';
}

function AddAttack($name, $type, $energy, $min, $max, $attack_chance, $success_chance, $resistable=0) {
	global $attacks;
	$i = count($attacks)+1;
	$attacks[$i]['name'] = $name;
	$attacks[$i]['type'] = $type;
	$attacks[$i]['energy'] = $energy;
	$attacks[$i]['min'] = $min;
	$attacks[$i]['max'] = $max;
	$attacks[$i]['attack_chance'] = $attack_chance;
	$attacks[$i]['success_chance'] = $success_chance;
	$attacks[$i]['attempted'] = 0;
	$attacks[$i]['hits'] = 0;
	$attacks[$i]['resistable'] = $resistable;
	$attacks[$i]['damage_resisted'] = 0;
	$attacks[$i]['attempt_dodged_resisted'] = 0;
	$attacks[$i]['no_energy'] = 0;
	$attacks[$i]['remaining_energy_yes'] = 0;
	$attacks[$i]['remaining_energy_no'] = 0;
	$attacks[$i]['total_damage'] = 0;
}

$energy_per_round = 1000;
$remaining_energy = 0;
$number_of_rounds = 10;
$attacks = array();
$max_round = 0;
$character_ac = 0;
$character_dr = 0;
$character_mr = 0;
$character_antimagic = 0;
$character_dodge = 0;

if (isset($_POST['name'])) {
	foreach ($_POST['name'] as $index => $value) {
		$name = (string)preg_replace("/[^A-Za-z0-9]/", '', $_POST['name'][$index]);
		$type = (string)$_POST['type'][$index] == 'spell' ? 'spell' : 'physical';
		$energy = (int)preg_replace("/[^0-9]/", '', $_POST['energy'][$index]);
		$min = (int)preg_replace("/[^0-9]/", '', $_POST['min'][$index]);
		$max = (int)preg_replace("/[^0-9]/", '', $_POST['max'][$index]);
		$resistable = (int)preg_replace("/[^0-2]/", '', $_POST['resistable'][$index]);
		$attack_chance = (int)preg_replace("/[^0-9]/", '', $_POST['attack_chance'][$index]);
		$success_chance = (int)preg_replace("/[^0-9]/", '', $_POST['success_chance'][$index]);
		if (!empty($name) && $energy > 0 && $max > 0 && $attack_chance > 0 && $success_chance > 0) {
			AddAttack($name,$type,$energy,$min,$max,$attack_chance,$success_chance,$resistable);
		}
	}
	
	$energy_per_round = (int)preg_replace("/[^0-9]/", '', $_POST['energy_per_round']);
	if ($energy_per_round > 10000) $energy_per_round = 10000;
	if ($energy_per_round < 1) $energy_per_round = 1;
	
	$number_of_rounds = (int)preg_replace("/[^0-9]/", '', $_POST['number_of_rounds']);
	if ($number_of_rounds > 100000) $number_of_rounds = 100000;
	if ($number_of_rounds < 1) $number_of_rounds = 1;
	
	$character_dodge = (int)preg_replace("/[^0-9]/", '', $_POST['character_dodge']);
	if ($character_dodge > 1000) $character_dodge = 1000;
	if ($character_dodge < 0) $character_dodge = 0;
	
	$character_ac = (int)preg_replace("/[^0-9]/", '', $_POST['character_ac']);
	if ($character_ac > 1000) $character_ac = 1000;
	if ($character_ac < 0) $character_ac = 0;
	
	$character_dr = (int)preg_replace("/[^0-9]/", '', $_POST['character_dr']);
	if ($character_dr > 1000) $character_dr = 0000;
	if ($character_dr < 0) $character_dr = 0;
	
	$character_mr = (int)preg_replace("/[^0-9]/", '', $_POST['character_mr']);
	if ($character_mr > 1000) $character_mr = 1000;
	if ($character_mr < 0) $character_mr = 0;
	
	$character_antimagic = isset($_POST['character_antimagic']) ? 1 : 0;
	
} else {
	//  		name		type		energy	min	max	attack%	success%
	AddAttack(	'physical',	'physical',	200,	10,	20,	45,		99);
	AddAttack(	'lbol',		'spell',	400,	5,	12,	35,		75);
	AddAttack(	'sbol',		'spell',	600,	10,	22,	20,		50);
}

?>
<HTML>
<HEAD>
<style>
body, th, td {
	font-family:Arial, sans-serif;
	font-size:10pt;
}
th, td {
	font-size:9pt;
}
input[type="number"] { width:55px; }

td { text-align:center; }
th { vertical-align:bottom; }

.red { color:#FD0004; }
.magenta { color:#F006FF; }
.green { color:#00A117; }
.bold { font-weight:bold; }

table.thinborders { border-collapse: collapse; }
table.thinborders td, table.thinborders th { border: 1px solid black; }

</style>
<title>MajorMUD Monster Attack Simulator</title>
</HEAD>
<BODY>
<form action="monster.php" method="post" enctype="multipart/form-data" name="form1" id="form1">
<table cellpadding="4" cellspacing="0" border="0">
<tr>
	<th>#</th><th>Name</th><th>Type</th><th>Resistable</th><th>Energy</th><th>Min</th><th>Max</th><th>Attack<br>Chance</th><th>Accy. or<br>Cast%</th>
</tr>
<?php for ($x = 1; $x <= 5; $x++):
	$name = empty($attacks[$x]['name']) ? '' : $attacks[$x]['name'];
	$type = empty($attacks[$x]['type']) ? '' : $attacks[$x]['type'];
	$resistable = empty($attacks[$x]['resistable']) ? 0 : $attacks[$x]['resistable'];
?>
<tr>
  <td><strong><?php echo $x; ?>.</strong></td>
  <td><input name="name[]" type="text" id="name<?php echo $x; ?>" size="7" value="<?php echo $name; ?>"></td>
  <td><select name="type[]" id="type<?php echo $x; ?>">
    <option value="spell" <?php if ($type=='spell') echo 'selected'; ?>>Spell</option>
    <option value="physical" <?php if ($type=='physical') echo 'selected'; ?>>Physical</option>
  </select></td>
  <td><select name="resistable[]" id="resistable<?php echo $x; ?>">
    <option value="0" <?php if ($resistable==0) echo 'selected'; ?>>No One</option>
    <option value="1" <?php if ($resistable==1) echo 'selected'; ?>>Anti-Magic</option>
    <option value="2" <?php if ($resistable==2) echo 'selected'; ?>>Everyone</option>
  </select></td>
    <?php foreach (array('energy', 'min', 'max', 'attack_chance', 'success_chance') as $var): 
		$$var = empty($attacks[$x][$var]) ? '' : $attacks[$x][$var];
	?>
  <td><input name="<?php echo $var; ?>[]" type="number" id="<?php echo $var.$x; ?>" value="<?php echo $$var; ?>"></td>
    <?php endforeach; ?>
<?php endfor; ?>
</table>
<br>
<strong>Character Info--</strong>

<label for="character_dodge">Dodge <strong>%</strong>:</label>
<input type="number" name="character_dodge" id="character_dodge" size="5" value="<?php echo $character_dodge; ?>"> &nbsp; 

<label for="character_ac">AC:</label>
<input type="number" name="character_ac" id="character_ac" size="5" value="<?php echo $character_ac; ?>"> &nbsp; 

<label for="character_dr">DR:</label>
<input type="number" name="character_dr" id="character_dr" size="5" value="<?php echo $character_dr; ?>"> &nbsp; 

<label for="character_mr">MR:</label>
<input type="number" name="character_mr" id="character_mr" size="5" value="<?php echo $character_mr; ?>"> &nbsp; 

<label for="character_antimagic">Anti-Magic?:</label>
<input type="checkbox" name="character_antimagic" id="character_antimagic" value="1" <?php echo $character_antimagic == "1" ? 'checked' : ''; ?>> &nbsp; 

<br><br>

<label for="number_of_rounds"># Rounds to Sim:</label>
<input type="text" name="number_of_rounds" id="number_of_rounds" size="5" value="<?php echo $number_of_rounds; ?>"> &nbsp; 

<label for="energy_per_round">Monster's Energy per Round:</label>
<input type="text" name="energy_per_round" id="energy_per_round" size="5" value="<?php echo $energy_per_round; ?>"> &nbsp; 

<br><br>

<input type="submit" name="submit_hide_rounds" id="submit_hide_rounds" value="Execute &amp; Show Stats Only"> &nbsp; <input type="submit" name="submit" id="submit" value="Execute &amp; Show Rounds"> (Under 5,000 rounds)

</form>
<?php
//normalize attack % for rand
for ($x = 2; $x <= count($attacks); $x++) {
	$attacks[$x]['attack_chance'] += $attacks[$x-1]['attack_chance'];
	if ($x == count($attacks) && $attacks[$x]['attack_chance'] != 100) p('<span style="font-size:1.5em;font-weight:bold;color:#FF9600;">Attack chances do not total 100%!</span>');
}

ob_start();
p('===============================================================');

$energy_stats['remaining_per_attack'] = 0;
$energy_stats['remaining_after_attack'] = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0);
$energy_stats['max_remaining'] = 0;
$energy_stats['total_used'] = 0;
$energy_stats['total_remaining'] = 0;

$total_damage = 0; $total_attacks = 0; $total_dodge = 0; $total_resisted = 0;
$last_attack['energy'] = 0; $last_attack['type'] = '';
for ($round = 1; $round <= $number_of_rounds && !empty($attacks); $round++) {
	p('<strong>ROUND '.$round.'</strong> / Energy: '.$remaining_energy.' + '.$energy_per_round.' = '.($remaining_energy+$energy_per_round));
	$remaining_energy += $energy_per_round;
	
	$x = 1;
	$round_damage = 0;
	while ($x <= 6) {
		
		$attack_chance = mt_rand(1,100);
		$last_attack['energy'] = 0; $last_attack['type'] = '';
		
		foreach ($attacks as $attack_num => $attack) {
			if ($attack_chance <= $attack['attack_chance']) {
				
				$last_attack['energy'] = $attack['energy'];
				$last_attack['type'] = $attack['type'];
				
				if ($remaining_energy < $attack['energy']) {
					
					p($attack['name'],'Not enough energy.');
					$attacks[$attack_num]['no_energy']++;
					$attacks[$attack_num]['remaining_energy_no'] += $remaining_energy;
					
				} else {
					
					$attacks[$attack_num]['attempted']++;
					$attacks[$attack_num]['remaining_energy_yes'] += $remaining_energy;
					$total_attacks++;
					
					$hit_chance = mt_rand(1,100);
					$attack_hit = false;
					$dodged = false; $glance = false; $resisted = false;
					
					$success_chance = $attack['success_chance'];
					if ($attack['type'] != 'spell') {
						if (empty($character_ac)) {
							$success_chance = 99;
						} else {
							//=((AC*AC)/100)/((ACCY*ACCY)/140)=fail %
							$success_chance = round(1-(($character_ac*$character_ac)/100)/(($success_chance*$success_chance)/140),2)*100;
						}
						if ($success_chance < 9) $success_chance = 9;
						if ($success_chance > 99) $success_chance = 99;
					}
					
					if ($hit_chance <= $success_chance) {
						$attack_hit = true;
						if ($attack['type'] != 'spell' && $character_dodge > 0) {
							$dodge_chance = mt_rand(1,100);
							if ($dodge_chance <= $character_dodge) { $attack_hit = false; $dodged = true; $attacks[$attack_num]['attempt_dodged_resisted']++; $total_dodge++; }
						}
					}
					
					if ($attack_hit) {	
						$damage = mt_rand($attack['min'],$attack['max']);
						if ($attack['type'] == 'spell') {
							if ($character_mr > 0) {
								if (($attack['resistable'] == 1 && $character_antimagic == 1) || $attack['resistable'] == 2) {
									//if( TYPEofRESIST=Never , 0 , IF( ANTI_MAGIC=Yes or TYPEofRESIST=Yes , IF( MR>196 , 0.98 , MR/200 ) , 0 ) )
									$resist_chance = mt_rand(1,100);
									if ( $resist_chance <= (($character_mr > 196 ? 196 : $character_mr)/2) ) {
										$attacks[$attack_num]['attempt_dodged_resisted']++; $total_resisted++;
										$damage = 0; $attack_hit = false; $resisted = true;
									}
								}
								
								if ($attack_hit) {	
									if ($character_antimagic == 0) {
										//DAMAGE = DAMAGE - ( DAMAGE * IF( MR<50, (MR-50)/100, IF( MR>150, 0.5, (MR-50)/200 ) ) )
										$mr_reduction = ($character_mr < 50 ? round(($character_mr-50)/100, 2) : ($character_mr > 150 ? 0.5 : round(($character_mr-50)/200, 2)) );
									} else {
										//DAMAGE = DAMAGE - ( DAMAGE * IF( MR>150 , 0.75 , MR/200 ) )
										$mr_reduction = ($character_mr > 150 ? 0.75 : round($character_mr/200, 2) );
									}
									$mr_reduction = ($damage * $mr_reduction);
									
									$attacks[$attack_num]['damage_resisted'] += $mr_reduction;
									$damage -= $mr_reduction;
								}
							}
						} else {
							$attacks[$attack_num]['damage_resisted'] += ($character_dr/2);
							$damage -= ($character_dr/2);
						}
						if ($damage <= 0) {
							if ($attack['type'] != 'spell') $glance = true;
							$damage = 0;
						}
						
						if ($attack_hit) {
							$total_damage += $damage;
							$round_damage += $damage;
							
							$remaining_energy -= $attack['energy'];
							$energy_stats['total_used'] += $attack['energy'];
							
							$attacks[$attack_num]['hits']++;
							$attacks[$attack_num]['total_damage'] += $damage;
							p(damage($attack['name'].' for '.$damage.($glance ? ' (GLANCE)' : '')),'Energy used: '.$attack['energy'].' ... Energy remaining: '.$remaining_energy);
						}
					}
					
					if (!$attack_hit) {		
						if ($attack['type'] == 'spell') {
							$energy_stats['total_used'] += round($attack['energy']/2,0);
							$remaining_energy -= round($attack['energy']/2,0);
							
							p($attack['name'].' ('.($resisted ? 'RESISTED' : 'FAIL').')','Energy used: '.round($attack['energy']/2,0).' ... Energy remaining: '.$remaining_energy);
						} else {
							$energy_stats['total_used'] += $attack['energy'];
							$remaining_energy -= $attack['energy'];
							
							p($attack['name'].' ('.($dodged ? 'DODGE' : 'MISS').')','Energy used: '.$attack['energy'].' ... Energy remaining: '.$remaining_energy);
						}
					}
				}
				
				break 1;
			}
		}
		$energy_stats['remaining_per_attack'] += $remaining_energy;
		$energy_stats['remaining_after_attack'][$x] += $remaining_energy;
		if ($last_attack['type'] != 'spell') {
			if ($remaining_energy < $last_attack['energy']) break 1;
		}
		$x++;
	}
	
	p('Damage for round: '.$round_damage.', Energy Remaining: '.$remaining_energy);
	if ($round_damage > $max_round) $max_round = $round_damage;
	
	if ($remaining_energy > $energy_stats['max_remaining']) $energy_stats['max_remaining'] = $remaining_energy;
	$energy_stats['total_remaining'] += $remaining_energy;
	
	p('===============================================================');
}
$attack_html = ob_get_clean();

if (!empty($attacks)):
?>
<table cellpadding="4" cellspacing="0" border="0" class="thinborders">
<tr>
	<th>Name</th>
    <th>Initial<br>Cast%</th>
    <th>True<br>Cast%</th>
    <th>Avg Attempt</th>
    <th>Attempts<br>/ Round</th>
    <th>Avg Round</th>
   	<th>Attempts</th>
    <th>Hits</th>
    <th>Success%</th>
    <th>DMG<br>Taken</th>
    <th>DMG<br>Resisted</th>
    <th>% DMG<br>Resisted</th>
    <th># Resisted<br>/ Dodged</th>
    <th>% Resisted<br>/ Dodged</th>
</tr>
<?php $last_cast_percent = 0; foreach ($attacks as $attack): ?>
<tr>
	<td><span class="bold"><?php echo $attack['name']; ?></span></td><!-- Name -->
    <td><?php echo ($attack['attack_chance']-$last_cast_percent); ?>%</td><!-- Initial Cast -->
    <td><span class="magenta bold"><?php echo (round($attack['attempted']/$total_attacks, 3)*100); ?>%</span></td><!-- True Cast -->
    <td><span class="red"><?php echo round($attack['total_damage']/$attack['attempted'],2); ?></span></td><!-- Avg Attempt -->
    <td><?php echo round($attack['attempted']/$number_of_rounds, 2); ?></td><!-- Attempts/Round -->
    <td><span class="red bold"><?php echo round($attack['total_damage']/$number_of_rounds,2); ?></span></td><!-- Avg Round -->
    <td><?php echo $attack['attempted']; ?></td><!-- Attempts -->
    <td><?php echo $attack['hits']; ?></td><!-- Hits -->
    <td><span class="green bold"><?php echo round($attack['hits']/$attack['attempted'], 3)*100; ?>%</span></td><!-- Success% -->
    <td><?php echo $attack['total_damage']; ?></td><!-- Total Dmg -->
    <td><?php echo $attack['damage_resisted']; ?></td><!-- DMG Resisted -->
    <td><?php echo $attack['total_damage'] > 0 ? round($attack['damage_resisted']/($attack['damage_resisted']+$attack['total_damage']), 3)*100 : 100; ?>%</td><!-- % DMG Resisted -->
    <td><?php echo $attack['attempt_dodged_resisted']; ?></td><!-- Resisted / Dodged -->
    <td><?php echo round(
		(
			$attack['attempt_dodged_resisted'] /
			($attack['type'] == 'spell' ? $attack['attempted'] : ($attack['hits'] + $attack['attempt_dodged_resisted']) )
		)
		, 3)*100; ?>%</td><!-- % Resisted / Blocked -->
</tr>
<?php $last_cast_percent = $attack['attack_chance']; endforeach; ?>
</table>
<h3><span class="red bold">AVG Damage / Round: <?php echo round($total_damage/$number_of_rounds,2); ?></span> ... <span class="red">Max Round Seen: <?php echo $max_round; ?></span></h3>
Total Rounds: <?php echo $number_of_rounds; ?>, Total Attacks: <?php echo $total_attacks; ?>, Total Damage: <?php echo $total_damage; ?>
<br><br>
<?php
/*$last_cast_percent = 0; 
foreach ($attacks as $attack) {	
	p('<strong>'.$attack['name'].'--</strong>');
	p('Hits: '.$attack['hits'].', '
		.($attack['type'] == 'spell' ? $attack['attempt_dodged_resisted'] > 0 ? 'Resists: '
		.$attack['attempt_dodged_resisted'].' ('.(round(($attack['attempt_dodged_resisted']/$attack['attempted']), 3)*100).'%), Fails' : 'Fails' : 'Misses').': '
		.($attack['attempted']-$attack['hits']).', Success %: '.(round($attack['hits']/$attack['attempted'], 3)*100));
	
	p('Total Damage: '.$attack['total_damage']
		.($attack['damage_resisted'] > 0 ? ' (Damage Resisted: '.$attack['damage_resisted'].', '
		.($attack['total_damage'] > 0 ? (round($attack['damage_resisted']/($attack['damage_resisted']+$attack['total_damage']), 3)*100) : 100).'%)': ''));
	
	p('');
	p(damage('Average Damage/'.($attack['type'] == 'spell' ? (($attack['attempted'] > $attack['hits']) ? 'Cast (including fails)':'Cast') : 'Swing').': '.round($attack['total_damage']/$attack['attempted'],2)));
	p('Average '.($attack['type'] == 'spell' ? 'Casts' : 'Swings').'/Round: '.round($attack['attempted']/$number_of_rounds,2));
	p(damage('Average Damage/Round: '.round($attack['total_damage']/$number_of_rounds,2)));
	p('');
	p('Initial Attack Chance: '.($attack['attack_chance']-$last_cast_percent).'%');
	p('<strong><span style="color:#F006FF;">True Attack Chance: '.(round($attack['attempted']/$total_attacks, 3)*100).'%</span></strong>');
	$last_cast_percent = $attack['attack_chance'];
}*/

/*p('');
p('Total Rounds: '.$number_of_rounds);
p('Total Attacks: '.$total_attacks);
p('Total Damage: '.$total_damage);
p('Max Damage Round Seen: '.$max_round);*/

/*p('<h3>'.damage('Total AVG Damage / Round: '.round($total_damage/$number_of_rounds,2)).'</h3>');*/
	
p('<strong>Advanced Energy Stats--</strong>');
foreach ($attacks as $attack) {	
	if ($attack['no_energy'] > 0) {
		p('');
		p('<span class="bold">'.$attack['name'].'--</span>');
		p('Attack chosen but not enough energy: '.$attack['no_energy'].' times, '.(round($attack['no_energy']/($attack['no_energy']+$attack['attempted']), 4)*100).'%');
		p('Average energy available when attack attempted: '.(round($attack['remaining_energy_yes']/$attack['attempted'], 0)));
		p('Average energy available when attack could not be attempted: '.(round($attack['remaining_energy_no']/$attack['no_energy'], 0)));
	}
}
p('');
p('Average Energy Used/Attack: '.round($energy_stats['total_used']/$total_attacks,2));
p('Average Energy Used/Round: '.round($energy_stats['total_used']/$number_of_rounds,2));
p('Average Energy Remaining/Attack: '.round($energy_stats['remaining_per_attack']/$total_attacks,2));
p('Average Energy Remaining/Round: '.round($energy_stats['total_remaining']/$number_of_rounds,2));
p('Max Energy Remaining Seen at End of Round: '.$energy_stats['max_remaining']);

p('');
for ($x = 1; $x <= 6; $x++) {
	p('Average Energy Remaining after atack '.$x.': '.round($energy_stats['remaining_after_attack'][$x]/$number_of_rounds,2));
}

//p('');
//echo var_export($attacks, true)."\r\n";

if (!isset($_POST['submit_hide_rounds'])) {
	if ($number_of_rounds <= 5000) {
		p('<PRE>');
		echo $attack_html;
		p('</PRE>');
	}
}

endif;
?>
</BODY></HTML>

