<?php


function logged_in ( ) {

    // already logged in! --> return true

    if (isset($_SESSION['user'])){
        return true;
    }

    global $ALLOW_NEW_USERS;
    global $USER_NAME_FILE;


    if (file_exists($USER_NAME_FILE)){
        include($USER_NAME_FILE);
        if (isset($_POST['reset'])){
            if ($_POST[email]){
                echo("Sending a message to reset your password (not yet implemented)!");
                // mail($_POST[email], 'OPUS-MT Leaderboard - reset password', "this function is not yet implemented");
            }
            else{
                echo('Specify a valid e-mail address to send a reset message!');
            }
        }
        elseif (isset($_POST['user']) && isset($_POST['password'])){
            // if (isset($USER[$_POST['user']])){
            if (array_key_exists($_POST['user'],$USER)){
                if (password_verify($_POST['password'], $USER[$_POST['user']])) {
                    $_SESSION['user'] = $_POST['user'];
                    $_SESSION['email'] = $_POST['email'];
                    return true;
                }                
                if ($ALLOW_NEW_USERS){
                    echo "<br /><br /><br />";
                    echo "User ".$_POST['user']." exists already!";
                    echo "<br />Try a different user name!<br/><br />";
                    return false;
                }
                else{
                    echo "Login failed! Try again!";
                }
            }
            elseif ($ALLOW_NEW_USERS){
                if (!$_POST['user']){
                    echo "No username given! Try again!";
                }
                elseif (userdir_exists($_POST['user'])){
                    echo "<br /><br /><br />";
                    echo "Username '".$_POST['user']."' exists already!<br />";
                    echo "Try again with a different name!<br /><br />";
                }
                elseif (add_user($USER_NAME_FILE,$_POST['user'],$_POST['password'],$_POST['email'])){
                    $_SESSION['user'] = $_POST['user'];
                    $_SESSION['email'] = $_POST['email'];
                    return true;
                }
                else{
                    echo "Creating user failed! Try again!";
                }
            }
            else{
                echo "Login failed! Try again!";
            }
        }
    }
    elseif (userdir_exists($_POST['user'])){
        echo "<br /><br /><br />";
        echo "Username '".$_POST['user']."' exists already!<br />";
        echo "Try again with a different name!<br /><br />";
    }
    elseif (add_user($USER_NAME_FILE,$_POST['user'],$_POST['password'],$_POST['email'])){
        $_SESSION['user'] = $_POST['user'];
        return true;
    }
    else{
        echo "<br /><br /><br />";
        echo "Login failed!<br />";
        echo "Try again!<br /><br />";
    }

    echo "<br /><br /><br /><h2>Login</h2>";

    if (!file_exists($USER_NAME_FILE)){
        echo "Create a user using the form below!<br/>";
        echo "Please use the following characters only: a-zA-Z0-9_<br /><br/>";
    }
    elseif ($ALLOW_NEW_USERS){
        echo "Login or create a new user using the form below!<br/>";
        echo "Please use the following characters only: a-zA-Z0-9_<br /><br/>";
    }


    echo "<form action=\"$PHP_SELF\" method=\"post\"><table>";
    echo '<tr><td>username:</td><td><input type="user" name="user"></td></tr>';
    echo '<tr><td>  e-mail:</td><td><input type="email" name="email"></td></tr>';
    echo '<tr><td>password:</td><td><input type="password" name="password"></td></tr>';
    echo '</table><p>';
    echo '<input type="submit" name="submit" value="login">';
    echo '<input type="submit" name="reset" value="reset password"></p>';
    echo '</form>';

    return false;

}


function userdir_exists($user){
    global $LEADERBOARD_DIR;
    if (file_exists(implode('/',[$LEADERBOARD_DIR,'models',$user]))){
        return true;
    }
    return false;
}


function add_user($passfile,$user,$password, $email){

    if (file_exists($passfile)){
        include($passfile);
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/',$user)){
        return false;
    }
    /*
    if (!preg_match('/^[a-zA-Z0-9_]+$/',$password)){
        return false;
    }
    */

    for ($i=1;$i<5;$i++){
        $fh = fopen($passfile,'w');
        if ($fh){
            flock( $fh, LOCK_EX );
            break;
        }
        sleep(1);
    }
    if ($fh){
        fwrite($fh,"<?php \n\$USER = array(\n");
        if (is_array($USER)){
            foreach ($USER as $name => $passw){
                fwrite($fh,"'".$name."' => '".$passw."',\n");
            }
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        fwrite($fh,"'".$user."' => '".$hash."'\n);\n");
        
        fwrite($fh,"\$EMAIL = array(\n");
        if (is_array($EMAIL)){
            foreach ($EMAIL as $name => $m){
                fwrite($fh,"'".$name."' => '".$m."',\n");
            }
        }
        fwrite($fh,"'".$user."' => '".$email."'\n);\n");
        
    }
    fwrite($fh,"?>\n");
    $lock = flock( $fh, LOCK_UN );
    fclose( $fh );

    return true;
}



?>
