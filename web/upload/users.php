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
        if (isset($_POST['user']) && isset($_POST['password'])){
            if (isset($USER[$_POST['user']])){
                if ($USER[$_POST['user']] === $_POST['password']){
                    $_SESSION['user'] = $_POST['user'];
                    return true;
                }
                if ($ALLOW_NEW_USERS){
                    echo "<br /><br /><br />";
                    echo "User ".$_POST['user']." exists already!";
                    echo "<br />Try a different user name!<br/><br />";
                    //		    login_form();
                    return false;
                }
                else{
                    echo "<br /><br /><br />";
                    echo "Login failed!<br />";
                    echo "Try again!<br /><br />";
                }
            }
            elseif ($ALLOW_NEW_USERS){
                if (userdir_exists($_POST['user'])){
                    echo "<br /><br /><br />";
                    echo "Username '".$_POST['user']."' exists already!<br />";
                    echo "Try again with a different name!<br /><br />";
                }
                elseif (add_user($USER_NAME_FILE,$_POST['user'],$_POST['password'])){
                    $_SESSION['user'] = $_POST['user'];
                    return true;
                }
                else{
                    echo "<br /><br /><br />";
                    echo "Creating user failed!<br />";
                    echo "Try again!<br /><br />";
                }
            }
            else{
                echo "<br /><br /><br />";
                echo "Login failed!<br />";
                echo "Try again!<br /><br />";
            }
        }
    }
    elseif (userdir_exists($_POST['user'])){
        echo "<br /><br /><br />";
        echo "Username '".$_POST['user']."' exists already!<br />";
        echo "Try again with a different name!<br /><br />";
    }
    elseif (add_user($USER_NAME_FILE,$_POST['user'],$_POST['password'])){
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

    echo "<form action=\"$PHP_SELF\" method=\"post\">";
    echo 'username: <input type="user" name="user"><br />';
    echo 'password: <input type="password" name="password"><br />';
    echo '<p><input type="submit" name="submit" value="login"></p>';
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


function add_user($passfile,$user,$password){

    if (file_exists($passfile)){
        include($passfile);
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/',$user)){
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/',$password)){
        return false;
    }

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
        fwrite($fh,"'".$user."' => '".$password."'\n);\n");
    }
    fwrite($fh,"?>\n");
    $lock = flock( $fh, LOCK_UN );
    fclose( $fh );

    return true;
}



?>
