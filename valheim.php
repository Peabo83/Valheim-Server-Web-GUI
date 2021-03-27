
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>

<style type="text/css">
  * {
    font-size: 1.5vw !important;
  }
  .row.login input {
    min-height: 2.7vw;
  }
  .row.login input.btn {
    padding: .2vw;
    position: relative;
    top: -.1vw;
  }
  .white {
    color: white;
  }
</style>

<?php
$haystack = shell_exec('ps -u steam');

$needle = 'valheim_server'; 
$pos = strpos($haystack, $needle);

if ($pos === false) {
    $message = "Valheim Server is down";
    $alert_class = "danger";
} else {
    $message = "Valheim Server is Up";
    $alert_class = "success";
}
?>

<body style="background-color: #222; padding: 2vw;">
  <div class="alert alert-<?php echo $alert_class; ?>" role="alert"><span class="glyphicon glyphicon-hdd" aria-hidden="true"></span> <?php echo $message; ?></div>

<?php
session_start();

// ***************************************** //
// ********** DECLARE VARIABLES  ********** //
// ***************************************** //

$username = 'Default_Admin';
$password = 'ch4n93m3';

$random1 = 'secret_key1';
$random2 = 'secret_key2';

$hash = md5($random1.$pass.$random2); 

$self = $_SERVER['REQUEST_URI'];

// ************************************ //
// ********** USER LOGOUT  ********** //
// ************************************ //

if(isset($_GET['logout'])) {
  unset($_SESSION['login']);
}

// ******************************************* //
// ********** USER IS LOGGED IN ********** //
// ******************************************* //

if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) {

  ?>
      
    <button class="btn btn-danger">Stop Valheim Service</button> <button class="btn btn-success">Start Valheim Service</button>
    <a class="btn btn-primary" href="?logout=true">Logout</a>
    <div class="row">
      <div class="col-md-12 white">
        <?php
          $info = shell_exec('systemctl status --no-pager -l valheimserver.service');

          $name = strstr($info, '-name');

          echo $name;
        ?>
      </div>
    </div>
      
  <?php
}

// *********************************************** //
// ********** FORM HAS BEEN SUBMITTED ********** //
// *********************************************** //

else if (isset($_POST['submit'])) {

  if ($_POST['username'] == $username && $_POST['password'] == $password){
  
    //IF USERNAME AND PASSWORD ARE CORRECT SET THE LOG-IN SESSION
    $_SESSION["login"] = $hash;
    header("Location: $_SERVER[PHP_SELF]");
    
  } else {
    
    // DISPLAY FORM WITH ERROR
    display_login_form();
    echo '<div class="alert alert-danger">Incorrect login information.</div>';
    
  }
}
    
// *********************************************** //
// ********** SHOW THE LOG-IN FORM  ********** //
// *********************************************** //

else {
  display_login_form();
}

function display_login_form() { ?>
<div class="row login">
  <div class="col-md-12">
    <form action="<?php echo $self; ?>" method='post'>
      <input type="text" name="username" id="username">
      <input type="password" name="password" id="password">
      <input class="btn btn-success" type="submit" name="submit" value="submit">
    </form>
  </div>
</div>
<?php } ?>

</body>
