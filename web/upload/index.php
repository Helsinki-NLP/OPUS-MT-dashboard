<?php session_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>OPUS-MT Dashboard - Uploads</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="../index.css" type="text/css">
</head>
<body>


<?php


include('../functions.php');
// include('../header.php');
include('users.php');

echo '<div class="header">';
if (isset($_SESSION["user"])){
    echo '  [logged in as: '.$_SESSION["user"].']';
    echo '  [<a href="index.php?session=clear">logout</a>]';
}
echo '  [<a href="../index.php">return to dashboard</a>]';
echo '<hr/>';
echo '</div>';



// echo('<h1>OPUS-MT leaderboard - Translation File Upload (User: '.$_SESSION['user'].')</h1>');
echo('<h1>OPUS-MT leaderboard - Translation File Upload</h1>');

check_setup();

$ALLOW_NEW_USERS = 1;
$USER_NAME_FILE = $local_datahome.'/Contributed-MT-leaderboard-data/users.php';
$LEADERBOARD_DIR = $leaderboard_dirs['contributed'];
$BENCHMARK_DIR = implode('/',[$LEADERBOARD_DIR,'OPUS-MT-testsets']);

if (!logged_in()){
	exit;
}

// home dir for uploaded data for the current user
$user_dir = implode('/',[$local_datahome,'Contributed-MT-leaderboard-data',$_SESSION['user']]);


$benchmark = isset($_POST["benchmark"]) ? $_POST["benchmark"] : '--select--';
$langpair = isset($_POST["langpair"]) ? $_POST["langpair"] : '--select--';
$system = isset($_POST["system"]) ? $_POST["system"] : '--select--';



// $lines = file(implode('/',[$BENCHMARK_DIR,'benchmark2langpair.tsv']));
$lines = read_file_with_cache(implode('/',[$BENCHMARK_DIR,'benchmark2langpair.tsv']));
$benchmarks = array();
foreach ($lines as $line) {
    list($b,$l) = explode("\t",rtrim($line));
    $benchmarks[$b] = $l;
}



echo('<form id="uploadform" action="index.php" method="post" enctype="multipart/form-data">');
echo '<table>';

if ($benchmark != ''){
    $langpairs = explode(' ',$benchmarks[$benchmark]);
    if (count($langpairs) == 1){
        $langpair = $langpairs[0];
    }
}


if ($benchmark != '--select--'){
    echo('<tr><td>benchmark: </td><td>'.$benchmark);
    echo('&nbsp;&nbsp;<a href="'.$_SERVER['PHP_SELF'].'"?session=clear>change</a>');
    echo('<input type="hidden" id="benchmark" name="benchmark" value="'.$benchmark.'"></td></tr>');
    $langpairs = explode(' ',$benchmarks[$benchmark]);
    if (count($langpairs) == 1){
        $langpair = $langpairs[0];
        echo('<tr><td>language pair: </td><td>'.$langpair);
        echo('<input type="hidden" id="langpair" name="langpair" value="'.$langpair.'"></td></tr>');
    }
    else{
        array_unshift($langpairs,'--select--');
        if (count($langpairs) > 100){
            $srclang = isset($_POST["srclang"]) ? $_POST["srclang"] : '--select--';
            $trglang = isset($_POST["trglang"]) ? $_POST["trglang"] : '--select--';
            echo '<tr><td>language pair: </td><td><select name="srclang" id="srclang" onchange="this.form.submit()">';
            $srclangs = array();
            $trglangs = array();
            foreach ($langpairs as $l){
                list($s,$t) = explode('-',$l);
                $srclangs[$s][$t]++;
                $trglangs[$t]++;
            }
            $trglangs['--select--']=1;
            $srclangs['--select--'] = $trglangs;
            foreach ($srclangs as $s => $t){
                if ($s == $srclang){
                    echo "<option value=\"$s\" selected>$s</option>";
                }
                else{
                    echo "<option value=\"$s\">$s</option>";
                }
            }
            echo '</select>';
            echo '<select name="trglang" id="trglang" onchange="this.form.submit()">';
            foreach ($srclangs[$srclang] as $t => $c){
                if ($t == $trglang){
                    echo "<option value=\"$t\" selected>$t</option>";
                }
                else{
                    echo "<option value=\"$t\">$t</option>";
                }
            }
            echo '</select></td></tr>';
            if ($srclang != '--select--' && $trglang != '--select--'){
                $langpair = implode('-',[$srclang,$trglang]);
                echo('<input type="hidden" id="langpair" name="langpair" value="'.$langpair.'"></td></tr>');
            }
        }
        else{
            echo '<tr><td>language pair: </td><td><select name="langpair" id="langpair" onchange="this.form.submit()">';
            foreach ($langpairs as $l){
                if ($l == $langpair){
                echo "<option value=\"$l\" selected>$l</option>";
                }
                else{
                    echo "<option value=\"$l\">$l</option>";
                }
            }
            echo '</select></td></tr>';
        }
    }
    if ($langpair != '--select--'){
        $systems = get_user_systems($user_dir);
        asort($systems);
        array_unshift($systems,'--select--');
        if (count($systems) > 0){
            echo '<tr><td>system name: </td><td><select name="system" id="system" onchange="this.form.submit()">';
            foreach ($systems as $s){
                if ($s == $system){
                    echo "<option value=\"$s\" selected>$s</option>";
                }
                else{
                    echo "<option value=\"$s\">$s</option>";
                }
            }
            echo '</select>';
        }
        echo('<tr><td>new system:</td><td><input type="text" name="newsystem"></td></tr>');
        echo('<tr><td>website:</td><td><input type="text" name="website"></td></tr>');
        echo('<tr><td>contact e-mail:</td><td><input type="email" name="email"></td></tr>');
        echo('<tr><td>translation file:</td>');
        echo('<td><input type="file" name="translations" id="translations"></td></tr>');
        echo('<tr><td><input type="submit" value="submit" name="submit"></td>');
        // echo('<td><input type="reset"></td></tr>');
    }
}
else{
    echo '<tr><td>benchmark: </td><td><select name="benchmark" id="benchmark" onchange="this.form.submit()">';
    $benchmarks['--select--'] = '';
    foreach ($benchmarks as $b => $l){
        if ($b == $benchmark){
            echo "<option value=\"$b\" selected>$b</option>";
        }
        else{
            echo "<option value=\"$b\">$b</option>";
        }
    }
    echo '</select></td></tr>';
}

echo('</table></form>');




if (isset($_POST['remove'])){
    $file = implode('/',[$user_dir,$_POST['system'],$_POST['testset']]);
    $file .= '.'.$_POST['langpair'];
    // echo("... remove $file ...");
    remove_user_file($_SESSION['user'],$_POST['system'],$_POST['testset'],$_POST['langpair']);
}

if (isset($_POST['confirm_removal'])){
    $file = implode('/',[$user_dir,$_POST['system'],$_POST['testset']]);
    $file .= '.'.$_POST['langpair'];
    // echo("... confirm removal of $file ...");
    $jobfile .= $file.'.remove.slurm';
    if (system("sbatch ".$jobfile)){
        echo "<br/>Remove job for ". htmlspecialchars(basename( $file )). " is in the queue (see below).";
        if (file_exists($file)){
            rename($file, $file.'.backup');
        }
        unlink($jobfile);
    }
}
elseif (isset($_POST['cancel_removal'])){
    // echo("... cancel removal ...");
    $slurm_file = implode('/',[$user_dir,$_POST['system'],$_POST['testset']]);
    $slurm_file .= '.'.$_POST['langpair'].'.remove.slurm';
    // echo("... cancel removal of $slurm_file ...");
    unlink($slurm_file);
}


elseif (isset($_POST['submit']) && isset($_POST['benchmark']) && isset($_POST['langpair'])){

    if ($_POST['newsystem'] != ''){
        $system = $_POST['newsystem'];
    }
    // $target_dir = implode('/',[$user_dir,$_POST['system']]);
    $target_dir = implode('/',[$user_dir,$system]);
    $target_file = implode('/',[$target_dir,$_POST['benchmark']]).'.'.$_POST['langpair'];

    echo('<br/><hr/><br/>');
    $uploadOk = 1;

    if ($langpair == '--select--'){
        echo "No language pair selected!";
        $uploadOk = 0;
    }
    // elseif (!preg_match('/^[a-zA-Z0-9_]+$/',$_POST['system'])){
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/',$system)){
        echo "No valid system name given! Please specify a non-empty ASCII name using characters in the range of [a-zA-Z0-9_]";
        $uploadOk = 0;
    }
    elseif (! filter_var($_POST['website'], FILTER_VALIDATE_URL)) {
        echo "Specify a valid website URL!";
        $uploadOk = 0;
    }
    elseif (! filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo "Specify a valid e-mail address for the contact person!";
        $uploadOk = 0;
    }
    elseif ($_FILES["translations"]['size'] == 0){
        echo "No file selected or the file is empty!";
        $uploadOk = 0;
    }
    elseif ($_FILES["translations"]["type"] != 'text/plain') {
        echo "Sorry, wrong file type. Select a plain text file!";
        $uploadOk = 0;
    }
    
    // Check file size
    elseif ($_FILES["translations"]["size"] > 500000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Check if file already exists
    /*
    elseif (file_exists($target_file)) {
        echo "Sorry, the file already exists.";
        $uploadOk = 0;
    }
    */

    else{
        // count lines and compare to benchmark
        list($srclang,$trglang) = explode('-',$_POST['langpair']);
        $testset_file = get_testset_filename($_POST['benchmark'], $_POST['langpair'], $srclang);
        // echo("retrieve $testset_file<br/>");
        $testset = read_file_with_cache(implode('/',[$testset_url,$testset_file]));
        if (count($testset) == 0){
            echo("problems retrieving $testset_url/$testset_file<br/>");
        }
        $output = file($_FILES["translations"]["tmp_name"]);
        if (count($output) !== count($testset)){
            echo("different number of lines for the selected benchmark and your translation file!<br/>");
            echo("Number of lines in ".basename($testset_file).": ".count($testset).'<br/>');
            echo("Number of lines in your file: ".count($output).'<br/>');
            $uploadOk = 0;
        }
    }

    if (file_exists($target_file)) {
        echo "The file already exists. The new upload will overwrite the old one!<br/>";
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 1) {
        if  (!file_exists($target_dir)){
            if (!mkdir($target_dir, 0775, true)) {
                echo("Could not create the user directory!");
            }
            else{
                chmod($target_dir, 0775);
            }
        }
        if  (file_exists($target_dir)){            
            if (move_uploaded_file($_FILES["translations"]["tmp_name"], $target_file)) {
                $jobfile = create_eval_job($_SESSION['user'],
                                           // $_POST['system'],
                                           $system,
                                           $_POST['website'],
                                           $_POST['email'],
                                           $_POST['benchmark'],
                                           $_POST['langpair'],
                                           $target_file);
                if (system("sbatch ".$jobfile)){
                    echo "<br/>Evaluation job for ". htmlspecialchars(basename( $target_file )). " is in the queue (see below).";
                }
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    }
}

echo("<h2>Existing user files</h2>");
show_userfiles($user_dir);


echo("<h2>Current Job Queue</h2>");

echo '<pre>';
system("squeue");
echo '</pre>';



function create_eval_job($user, $system, $website, $email, $benchmark, $langpair, $file){

    global $leaderboard_dirs;
    
    $slurmfile = $file.'.slurm';
    if (!$fp = fopen($slurmfile, 'w')) {
        echo "Cannot open file ($slurmfile)";
        return '';
    }
    fwrite($fp, "#!/bin/bash\n\n");
    fwrite($fp, "#SBATCH -J '$user/$system/$benchmark.$langpair'\n");
    fwrite($fp, "#SBATCH -o '$file.out'\n");
    fwrite($fp, "#SBATCH -e '$file.err'\n\n");
    fwrite($fp, "make -C ".$leaderboard_dirs['contributed']);
    fwrite($fp, " USER=".$user);
    fwrite($fp, " MODELNAME=".$system);
    fwrite($fp, " WEBSITE='".$website."'");
    fwrite($fp, " CONTACT='".$email."'");
    fwrite($fp, " BENCHMARK=".$benchmark);
    fwrite($fp, " LANGPAIR=".$langpair);
    fwrite($fp, " FILE=".$file);
    fwrite($fp, " eval\n\n");
    fclose($fp);
    return $slurmfile;
}

function create_remove_job($user, $system, $benchmark, $langpair, $file){

    global $leaderboard_dirs;
    
    $slurmfile = $file.'.remove.slurm';
    if (!$fp = fopen($slurmfile, 'w')) {
        echo "Cannot open file ($slurmfile)";
        return '';
    }
    fwrite($fp, "#!/bin/bash\n\n");
    fwrite($fp, "#SBATCH -J '$user/$system/$benchmark.$langpair'\n");
    fwrite($fp, "#SBATCH -o '$file.remove.out'\n");
    fwrite($fp, "#SBATCH -e '$file.remove.err'\n\n");
    fwrite($fp, "make -C ".$leaderboard_dirs['contributed'].'/admin');
    fwrite($fp, " USER=".$user);
    fwrite($fp, " MODELNAME=".$system);
    fwrite($fp, " BENCHMARK=".$benchmark);
    fwrite($fp, " LANGPAIR=".$langpair);
    fwrite($fp, " remove\n\n");
    fclose($fp);
    return $slurmfile;
}



include('../footer.php');


function get_user_systems($homedir){
    $systems = array();
    if ($handle = opendir($homedir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $sysdir = implode('/',[$homedir,$entry]);
                if (is_dir($sysdir)){
                    array_push($systems, $entry);
                }
            }
        }
    }
    return $systems;
}

function show_userfiles($homedir){
    $systems = get_user_systems($homedir);
    echo '<table>';
    foreach ($systems as $system){
        $sysdir = implode('/',[$homedir,$system]);
        if ($sysdh = opendir($sysdir)) {
            while (false !== ($file = readdir($sysdh))) {
                $localfile = implode('/',[$sysdir,$file]);
                if (is_file($localfile)){
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if ($ext != 'out' && $ext != 'err' && $ext != 'slurm' && $ext != 'backup'){
                        echo('<tr><td>');
                        list($testset, $langpair) = explode('.', $file);
                        // echo "$system / $testset . $langpair<br/>";
                        $system_param = make_query(['model' => implode('/',[$_SESSION['user'],$system]),
                                                    'test' => 'all',
                                                    'session' => 'clear',
                                                    'pkg' => 'contributed']);
                        $file_param = make_query(['model' => implode('/',[$_SESSION['user'],$system]),
                                                  'test' => $testset,
                                                  'langpair' => $langpair,
                                                  'scoreslang' => $langpair,
                                                  'session' => 'clear',
                                                  'pkg' => 'contributed']);
                        echo "<a href='../index.php?$system_param'>$system</a> / <a href='../index.php?$file_param'>$file</a><br/>";
                        $sysfile = implode('/',[$system,$file]);
                        echo('<td><form style="margin: 0px 0px 0px 0px;;" action="index.php" method="post">');
                        echo('<input type="hidden" name="system" value="'.$system.'">');
                        echo('<input type="hidden" name="testset" value="'.$testset.'">');
                        echo('<input type="hidden" name="langpair" value="'.$langpair.'">');
                        if (file_exists($localfile.'.remove.slurm')){
                            echo('<input type="submit" value="cancel" name="cancel_removal">');
                            echo('<input type="submit" value="confirm removal" name="confirm_removal">');
                        }
                        else{
                            echo('<input type="submit" value="remove" name="remove">');
                        }
                        echo('</form></td></tr>');
                    }
                }
            }
        }
        closedir($sysdh);
    }
    echo('</table>');
}


function remove_user_file($user, $system, $testset, $langpair){
    global $user_dir;

    $user_file = implode('/',[$user_dir, $system, $testset]).'.'.$langpair;
    create_remove_job($user, $system, $testset, $langpair, $user_file);
    // echo("... remove ".implode('/',[$user, $system, $testset, $langpair]));
    echo('<br/>');
}


function check_setup(){
    global $leaderboard_dirs;
    if (!exec('which squeue')){
        echo("SLURM is not available! Upload is disabled!<br/>");
        exit;
    }
    if (!file_exists($leaderboard_dirs['contributed'])){
        echo("Local leaderboard repository does not exist! Upload is disabled!<br/>");
        exit;
    }
}



?>


</body>
</html>
