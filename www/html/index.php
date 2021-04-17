<?php

require '/var/www/VSW-GUI-CONFIG';

$mod_file_count = new FilesystemIterator("/home/steam/valheimserver/BepInEx/config", FilesystemIterator::SKIP_DOTS);

if ($mod_file_count > 0 && $cfg_editor == true) {
  require 'pheditor.php';
}

if (isset($_GET['start'])) {
  $info = exec('sudo systemctl start valheimserver.service');
  header("Location: $_SERVER[PHP_SELF]");
  exit;
}
if (isset($_GET['stop'])) {
  $info = exec('sudo systemctl stop valheimserver.service');
  header("Location: $_SERVER[PHP_SELF]");
  exit;
}
if (isset($_GET['restart'])) {
  $info = exec('sudo systemctl restart valheimserver.service');
  header("Location: $_SERVER[PHP_SELF]");
  exit;
}

          // Get the status of valheimserver.service
          $info = shell_exec('systemctl status --no-pager -l valheimserver.service');
          $plugin_config_files = shell_exec("ls /home/steam/valheimserver/BepInEx/config/");

          // Pull all the values of of the output of $info
          $startup_line = strstr($info, '-name');    
          $name = str_replace("-name ", "", substr($startup_line, 0, strpos($startup_line, "-port")));
          $port = strstr($info, '-port');
          $port = str_replace("-port ", "", substr($port, 0, strpos($port, "-world")));
          $world = strstr($info, '-world');
          $world = str_replace("-world ", "", substr($world, 0, strpos($world, "-password")));
          $world_perm = $world;
          $public = strstr($info, '-public');
          $public = str_replace("-public ", "", $public);
          switch ($public) {
            case 0:
              $public_status = "Not Public";
              $public_class = "warning";
              break;
            case 1:
              $public_status = "Public";
              $public_class = "success";
            default:
              $public_status = "Error fetching data";
              $public_class = "danger";
              break;
          };
          $active = strstr($info, 'Active:');
          $active = str_replace("Active: ", "", substr($active, 0, strpos($active, ";")));
          $needle = "(dead)";
          $pos = strpos($info, $needle);
          if ($pos > 0) {
            $alert_class = "danger";
            $world = "<span class='glyphicon glyphicon-remove red'></span>";
            $port = "<span class='glyphicon glyphicon-remove red'></span>";
            $public = "NONE";
            $name = "Valheim Service Not Running";
            $public_status = "<span class='glyphicon glyphicon-remove red'></span>";
            $public_class = "danger";
            $public_attr = "disabled";
            $no_download = '';
            $no_download_class = 'success';
            $url_copy = 'hidden';
            $start_attr = '';
          } else {
            $alert_class = "success";
            $public_attr = "";
            $no_download = "disabled data-toggle=\"tooltip\" data-placement=\"top\" title=\"Must Stop Server to Download\"";
            $no_download_class = "danger";
            $url_copy = '';
            $start_attr = 'disabled';
          }

if (isset($_GET['download_db'])) {
  $command = exec('sudo cp -R /home/steam/.config/unity3d/IronGate/Valheim/worlds/* /var/www/html/download/');
  $dir    = '/var/www/html/download/';
  $files = scandir($dir);
  foreach ($files as $key => $value) {
    $ext  = (new SplFileInfo($value))->getExtension();
    if ($ext == 'db' ) {
      header('location: /download/'.$value);
      exit;
    }
  }
  trigger_error('No .db file found, check permissions and try again.');
  exit;
}

if (isset($_GET['download_fwl'])) {
  $command = exec('sudo cp -R /home/steam/.config/unity3d/IronGate/Valheim/worlds/* /var/www/html/download/');
  $dir    = '/var/www/html/download/';
  $files = scandir($dir);
  foreach ($files as $key => $value) {
    $ext  = (new SplFileInfo($value))->getExtension();
    if ($ext == 'fwl' ) {
      header('location: /download/'.$value);
      exit;
    }
  }
  trigger_error('No .db file found, check permissions and try again.');
  exit;
}

session_start();

// Hide mods accordion panel for users logged in
if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) {
  $mods_accordion = 'collapse';
  $server_accordion = 'collapse in';
} else {
  $mods_accordion = 'collapse in';
  $server_accordion = 'collapse';
}

// ********** USER LOGOUT  ********** //
if(isset($_GET['logout'])) {
  unset($_SESSION['login']);
  header("Location: $_SERVER[PHP_SELF]");
  exit;
}

// ********** Form has been submitted ********** //
      if (isset($_POST['submit'])) {
        if ($_POST['username'] == $username && $_POST['password'] == $password){
          // If username and password correct, log in
          $_SESSION["login"] = $hash;
          header("Location: $_SERVER[PHP_SELF]");    
        } else {      
          // Display error on bad login
          display_login_form();
          echo '<div class="alert alert-danger">Incorrect login information.</div>';
          exit;
        }
      }

?>
<html>
  <head>
    <!-- JQuery and Bootstrap libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.7/themes/default/style.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/codemirror.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/addon/lint/lint.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/addon/dialog/dialog.min.css">
    <link rel="stylesheet" href="custom.css">
    <script type="text/javascript" src="custom.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
  <?php
  if ($mod_file_count > 0 && $cfg_editor == true) {?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.7/jstree.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/mode/htmlmixed/htmlmixed.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/mode/markdown/markdown.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jshint/2.10.2/jshint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsonlint/1.6.0/jsonlint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/addon/lint/lint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/addon/lint/javascript-lint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/addon/lint/json-lint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/addon/lint/css-lint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/addon/search/search.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/addon/search/searchcursor.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/addon/search/jump-to-line.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.43.0/addon/dialog/dialog.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/js-sha512/0.8.0/sha512.min.js"></script>
  <?php }; // End If // ?>
</head>
        <body style="background-color: #222; padding: 2vw;">
          <div class="wrapper">
          <div id="loading-background" class="hidden">
            <div id="loading-body" class="panel panel-default">
              <div class="spinner-grow text-primary" role="status">
                <span class="sr-only">Loading...</span>
              </div>
              Server Executing Command, Please wait.
            </div>
          </div>


              <div class="row alert alert-<?php echo $alert_class; ?>" role="alert">
                <div class="col-8 h6"><span class="glyphicon glyphicon-hdd" aria-hidden="true"></span> <?php echo $active; ?></div>
                <div class="col-4 <?php echo $url_copy;?>">
                      <button id="copyButton" title="Click to copy" class="btn input-group-addon btn-<?php echo $alert_class;?>" <?php echo $alert_attr;?>><span class="glyphicon glyphicon-copy"></span></button>
                      <input type="text" id="copyTarget" class="form-control" value="<?php echo $realIP . ':' . $port;?>">
                </div>
              </div>
        <?php
        if ($mod_file_count > 0 && $show_mods == true) {
        ?>
          <div class="row">
            <div class="col-12">
              <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                <div class="panel panel-primary">
                  <div class="panel-heading" role="tab" id="headingOne">
                    <h4 class="panel-title">
                      <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        Installed Mods
                      </a>
                    </h4>
                  </div>
                  <div id="collapseOne" class="panel-collapse <?php echo $mods_accordion; ?>" role="tabpanel" aria-labelledby="headingOne">
                    <div class="panel-body">
                      <div class="row">
                      <?php

                        $files = scandir('/home/steam/valheimserver/BepInEx/config');
                        foreach($files as $file) {
                          $new_str = "";
                          $full_file_name = "/home/steam/valheimserver/BepInEx/config/" . $file;
                          $lines_array = file($full_file_name);
                          $search_string = "nexusID";

                          foreach($lines_array as $line) {
                              if(strpos($line, $search_string) !== false) {
                                  list(, $new_str) = explode(" = ", $line);
                                  $new_str = str_replace(array("\r", "\n"), '', $new_str);
                              }
                          }

                          if (!empty($new_str)) {
                            $url = "https://www.nexusmods.com/valheim/mods/" . $new_str;
                            $fp = file_get_contents($url);
                            $res = preg_match("/<title>(.*)<\/title>/siU", $fp, $title_matches);
                            $res2 = preg_match("/<meta name=\"description\" content=\"(.*)\"/siU", $fp, $description_matches);
                            $res3 = preg_match("/<meta property=\"og:image\" content=\"(.*)\"/siU", $fp, $image_matches);
                            $title = preg_replace('/\s+/', ' ', $title_matches[1]);
                            $title = trim($title);
                            $title = str_replace("at Valheim Nexus - Mods and community", "", $title);
                            $description = preg_replace('/\s+/', ' ', $description_matches[1]);
                            $image = preg_replace('/\s+/', ' ', $image_matches[1]);

                          echo "<div class='col-md-4'><div class='thumbnail'><a target='_blank' href='" . $url . "'><img src='" . $image . "'><div class='caption'>" . $title .  "</a></div>" . $description . "</div></div>";

                          }
                        }
                        foreach ($manual_add_displayed_mods as $key => $value) {
                            $url = "https://www.nexusmods.com/valheim/mods/" . $value;
                            $fp = file_get_contents($url);
                            $res = preg_match("/<title>(.*)<\/title>/siU", $fp, $title_matches);
                            $res2 = preg_match("/<meta name=\"description\" content=\"(.*)\"/siU", $fp, $description_matches);
                            $res3 = preg_match("/<meta property=\"og:image\" content=\"(.*)\"/siU", $fp, $image_matches);
                            $title = preg_replace('/\s+/', ' ', $title_matches[1]);
                            $title = trim($title);
                            $title = str_replace("at Valheim Nexus - Mods and community", "", $title);
                            $description = preg_replace('/\s+/', ' ', $description_matches[1]);
                            $image = preg_replace('/\s+/', ' ', $image_matches[1]);

                          echo "<div class='col-md-4'><div class='thumbnail'><a target='_blank' href='" . $url . "'><img src='" . $image . "'><div class='caption'>" . $title .  "</a></div>" . $description . "</div></div>";
                        }
                      ?>
                    </div>
                    </div>
                  </div>
                </div>

        <?php
        };

        if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) {
        // *************************************** //
        // ********** Logged In Content ********** //
        // *************************************** //
        ?>
      <div class="panel panel-primary">
        <div class="panel-heading" role="tab" id="headingTwo">
          <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
              <?php echo $name; ?>
            </a>
          </h4>
        </div>
        <div id="collapseTwo" class="panel-collapse <?php echo $server_accordion;?>" role="tabpanel" aria-labelledby="headingTwo">
          <div class="panel-body">
            <label class="label label-info">Port</label> <?php echo $port; ?>
            <label class="label label-info">World</label> <?php echo $world; ?>
            <label class="label label-info">Public</label> <?php echo $public_status; ?><br><br>
            <button class="btn btn-danger server-function" onclick="location.href='index.php?stop=true';" <?php echo $public_attr;?>>Stop</button> 
            <button class="btn btn-success server-function" onclick="location.href='index.php?start=true';" <?php echo $start_attr;?>>Start</button> 
            <button class="btn btn-warning server-function" onclick="location.href='index.php?restart=true';" <?php echo $public_attr;?>>Restart</button> 
            <button class="btn btn-<?php echo $no_download_class;?>" <?php echo $no_download; ?> onclick="location.href='index.php?download_db=true';">Download DB</button> 
            <button class="btn btn-<?php echo $no_download_class;?>" onclick="location.href='index.php?download_fwl=true';" <?php echo $no_download; ?>>Download FWL</button> <a class="btn btn-primary" href="?logout=true">Logout</a>
          </div>
        </div>
      </div>

      <?php
      if ($mod_file_count > 0 && $cfg_editor == true) {
      ?>


      <div class="panel panel-primary">
        <div class="panel-heading" role="tab" id="headingThree">
          <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
              Mod CFG Editor ( <?php echo $cfg_editor; ?>)
            </a>
          </h4>
        </div>
        <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
          <div class="panel-body">
            <div class="col-md-9">
                <div class="float-left">
                  <div class="dropdown float-left">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="fileMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">File</button>
                    <div class="dropdown-menu" aria-labelledby="fileMenu">
                      <?php if (in_array('newfile', $permissions) || in_array('editfile', $permissions)) { ?>
                        <a class="dropdown-item save disabled" href="javascript:void(0);">Save <span class="float-right text-secondary">S</span></a>
                      <?php } ?>
                      <a class="dropdown-item close disabled" href="javascript:void(0);">Close <span class="float-right text-secondary">C</span></a>
                    </div>
                  </div>
                  <span id="path" class="btn float-left"></span>
                </div>
              </div>
            </div>

            <div class="row px-3">
              <div class="col-lg-3 col-md-3 col-sm-12 col-12">
                <div id="files" class="card">
                  <div class="card-block"><?= files(MAIN_DIR) ?></div>
                </div>
              </div>

              <div class="col-lg-9 col-md-9 col-sm-12 col-12">
                <div class="card">
                  <div class="card-block">
                    <div id="loading">
                      <div class="lds-ring">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                      </div>
                    </div>
                    <textarea id="editor" data-file="" class="form-control"></textarea>
                    <input id="digest" type="hidden" readonly>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <form method="post" id="editor_form">
            <input name="action" type="hidden" value="upload-file">
            <input name="destination" type="hidden" value="">

            <div class="modal" id="uploadFileModal">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h4 class="modal-title">Upload File</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                  </div>
                  <div class="modal-body">
                    <div>
                      <input name="uploadfile[]" type="file" value="" multiple>
                    </div>
                    <?php

                    if (function_exists('ini_get')) {
                      $sizes = [
                        ini_get('post_max_size'),
                        ini_get('upload_max_filesize')
                      ];

                      $max_size = max($sizes);

                      echo '<small class="text-muted">Maximum file size: ' . $max_size . '</small>';
                    }

                    ?>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-dismiss="modal">Upload</button>
                  </div>
                </div>
              </div>
            </div>
          </form>
          </div>
        </div>
      </div>              
      <?php
      }; ?>

          <!-- close accordion -->
          </div>
          </div>
          </div>
        <!-- Close wrapper -->
        </div>
        </body>

        </html>
      </div>
      <?php
      }
  // ********** Login Form  ********** //
  else {
    echo '</div></div></div>';
    display_login_form();
  }

  function display_login_form() { ?>
    <form action="<?php echo $self; ?>" method='post'>
    <div class="row login">
          <div class="col-5"><input type="text" name="username" id="username" class="form-control"></div>
          <div class="col-5"><input type="password" name="password" id="password" class="form-control"></div>
          <div class="col-2"><input class="btn btn-success" type="submit" name="submit" value="submit"></div>
        </form>
    </div>
  <?php } ?>
  </body>
</html>
