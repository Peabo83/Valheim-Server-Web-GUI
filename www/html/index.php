<?php

// Get the config file
require '/var/www/VSW-GUI-CONFIG';

// Check to make sure the config file exists, or set the editor to false
if (file_exists('/home/steam/valheimserver/BepInEx/config')) {
  $mod_file_count = new FilesystemIterator("/home/steam/valheimserver/BepInEx/config", FilesystemIterator::SKIP_DOTS);
} else {
  $cfg_editor = false;
}

// If everything needed to edit CFGs is in order, get the needed php script
if ($mod_file_count > 0 && $cfg_editor == true) {
  require 'pheditor.php';
}

session_start();

// Verify user then check $_GET values for issued server commands
if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) {
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
  if (isset($_GET['seed'])) {
    $command = exec('sudo cp -R /home/steam/.config/unity3d/IronGate/Valheim/worlds/* /var/www/html/download/');
    header("Location: $_SERVER[PHP_SELF]");
    exit;
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
  if (isset($_GET['add_admin'])) {
    $ID = preg_replace("/[^0-9]/", "", $_GET['add_admin'] );
    $full_command = "sudo echo '".$ID."' | sudo tee -a /home/steam/.config/unity3d/IronGate/Valheim/adminlist.txt";
    $command = exec($full_command);
    header("Location: $_SERVER[PHP_SELF]");
    exit;
  }
  if (isset($_GET['remove_admin'])) {
    $ID = preg_replace("/[^0-9]/", "", $_GET['remove_admin'] );
    $full_command = "sudo sed -i '/^".$ID."/d' /home/steam/.config/unity3d/IronGate/Valheim/adminlist.txt";
    $command = exec($full_command);
    header("Location: $_SERVER[PHP_SELF]");
    exit;
  }
  if (isset($_GET['add_ban'])) {
    $ID = preg_replace("/[^0-9]/", "", $_GET['add_ban'] );
    $full_command = "sudo echo '".$ID."' | sudo tee -a /home/steam/.config/unity3d/IronGate/Valheim/bannedlist.txt";
    $command = exec($full_command);
    header("Location: $_SERVER[PHP_SELF]");
    exit;
  }
  if (isset($_GET['remove_ban'])) {
    $ID = preg_replace("/[^0-9]/", "", $_GET['remove_ban'] );
    $full_command = "sudo sed -i '/^".$ID."/d' /home/steam/.config/unity3d/IronGate/Valheim/bannedlist.txt";
    $command = exec($full_command);
    header("Location: $_SERVER[PHP_SELF]");
    exit;
  }
  if (isset($_GET['ID_to_Verify'])) {
    $ID_to_Verify = preg_replace("/[^0-9]/", "", $_GET['ID_to_Verify'] );    
    $url = "https://steamidfinder.com/lookup/" . $ID_to_Verify;
    $fp = file_get_contents($url);
    $res = preg_match("/<title>(.*)<\/title>/siU", $fp, $title_matches);
    $title_array = explode(" ", $title_matches[1]);
    if ($title_array[0] == "steam" || $title_array[0] == "404" || $title_array[0] == "") {
      $Verified_ID = 'UNVERIFIED';
    } else {
      $Verified_ID = $ID_to_Verify;
    }
  }
}

// Get the status of valheimserver.service
$info = shell_exec('systemctl status --no-pager -l valheimserver.service');
$plugin_config_files = shell_exec("ls /home/steam/valheimserver/BepInEx/config/");

// Pull all the values out of $info into more useful variables
$startup_line = strstr($info, '-name');    
$name = str_replace("-name ", "", substr($startup_line, 0, strpos($startup_line, "-port")));
$port = strstr($info, '-port');
$port = str_replace("-port ", "", substr($port, 0, strpos($port, "-world")));
$world = strstr($info, '-world');
$world = str_replace("-world ", "", substr($world, 0, strpos($world, "-password")));
$world = str_replace(" ", "", $world);
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

// If the FWL has been copied to /download run it through a hexdump and then clean the ASCII output to something legible
if (file_exists("/var/www/html/download/".$world.".fwl")) {
  $raw_fwl = shell_exec("hexdump -C /var/www/html/download/".$world.".fwl");
  $tempy = preg_match_all("/\|(.*)\|/siU", $raw_fwl, $hexdata_matches);
  $seed = $hexdata_matches[0][0] . $hexdata_matches[0][1];
  $seed = str_replace('.', ' ', $seed);
  $seed = str_replace('|', '', $seed);
  $seed_array = explode(' ', $seed);
  foreach ($seed_array as $key => $value) {
    if (!empty($value)) {
      $seed_output_array[] = $value;
    }
  }
  $seed = $seed_output_array[2];
  $has_seed = true;
  if ($make_seed_public == true) {
    $text_row = '7';
  } else {
    $text_row = '8';
  }
} else {
  $seed = "<button class=\"btn btn-xs btn-success\" onclick=\"location.href='index.php?seed=true';\">Get Seed</button>";
  $has_seed = false;
  $text_row = '8';
}

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
  unset($_SESSION['pheditor_admin']);
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
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
  <?php
  if ($mod_file_count > 0 && $cfg_editor == true) {
  ?>
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
  <script type="text/javascript">
    var editor,
      modes = {
        "js": "javascript",
        "json": "javascript",
        "md": "text/x-markdown"
      },
      last_keyup_press = false,
      last_keyup_double = false,
      terminal_history = 1;

    function alertBox(title, message, color) {
      iziToast.show({
        title: title,
        message: message,
        color: color,
        position: "bottomRight",
        transitionIn: "fadeInUp",
        transitionOut: "fadeOutRight",
      });
    }

    function reloadFiles(hash) {
      $.post("<?= $_SERVER['PHP_SELF'] ?>", {
        action: "reload"
      }, function(data) {
        $("#files > div").jstree("destroy");
        $("#files > div").html(data.data);
        $("#files > div").jstree();
        $("#files > div a:first").click();
        $("#path").html("");

        window.location.hash = hash || "/";

        if (hash) {
          $("#files a[data-file=\"" + hash + "\"], #files a[data-dir=\"" + hash + "\"]").click();
        }
      });
    }

    function setCookie(name, value, timeout) {
      if (timeout) {
        var date = new Date();
        date.setTime(date.getTime() + (timeout * 1000));
        timeout = "; expires=" + date.toUTCString();
      } else {
        timeout = "";
      }

      document.cookie = name + "=" + encodeURIComponent(value) + timeout + "; path=/";
    }

    function getCookie(name) {
      var cookies = document.cookie.split(';');

      for (var i = 0; i < cookies.length; i++) {
        if (cookies[i].trim().indexOf(name + "=") == 0) {
          return decodeURIComponent(cookies[i].trim().substring(name.length + 1).trim());
        }
      }

      return false;
    }

    $(function() {

      // Copy IP:Port Button Code
      "use strict";
      function copyToClipboard(elem) {
        var target = elem;

        // select the content
        var currentFocus = document.activeElement;

        target.focus();
        target.setSelectionRange(0, target.value.length);

        // copy the selection
        var succeed;

        try {
          succeed = document.execCommand("copy");
        } catch (e) {
          console.warn(e);

          succeed = false;
        }

        // Restore original focus
        if (currentFocus && typeof currentFocus.focus === "function") {
          currentFocus.focus();
        }

        if (succeed) {
          $(".copied").animate({ top: -25, opacity: 0 }, 700, function() {
            $(this).css({ top: 0, opacity: 1 });
          });
        }

        return succeed;
      }

      $("#copyButton, #copyTarget").on("click", function() {
        copyToClipboard(document.getElementById("copyTarget"));
      });
      // End IP:Port Button Code

      // Start Text editor Code
      editor = CodeMirror.fromTextArea($("#editor")[0], {
        <?php if (empty(EDITOR_THEME) === false) : ?>
          theme: "<?= EDITOR_THEME ?>",
        <?php endif; ?>
        lineNumbers: true,
        mode: "application/x-httpd-php",
        indentUnit: 4,
        indentWithTabs: true,
        lineWrapping: true,
        gutters: ["CodeMirror-lint-markers"],
        lint: true
      });

      $("#files > div").jstree({
        state: {
          key: "pheditor"
        },
        plugins: ["state"]
      });

      $("#files").on("dblclick", "a[data-file]", function(event) {
        event.preventDefault();
        <?php

        $base_dir = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace(DS, '/', MAIN_DIR));

        if (substr($base_dir, 0, 1) !== '/') {
          $base_dir = '/' . $base_dir;
        }

        ?>
        window.open("<?= $base_dir ?>" + $(this).attr("data-file"));
      });

      $("a.change-password").click(function() {
        var password = prompt("Please enter new password:");

        if (password != null && password.length > 0) {
          $.post("<?= $_SERVER['PHP_SELF'] ?>", {
            action: "password",
            password: password
          }, function(data) {
            alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");
          });
        }
      });

      $(".dropdown .new-file").click(function() {
        var path = $("#path").html();

        if (path.length > 0) {
          var name = prompt("Please enter file name:", "new-file.php"),
            end = path.substring(path.length - 1),
            file = "";

          if (name != null && name.length > 0) {
            if (end == "/") {
              file = path + name;
            } else {
              file = path.substring(0, path.lastIndexOf("/") + 1) + name;
            }

            $.post("<?= $_SERVER['PHP_SELF'] ?>", {
              action: "save",
              file: file,
              data: ""
            }, function(data) {
              alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");

              if (data.error == false) {
                reloadFiles();
              }
            });
          }
        } else {
          alertBox("Warning", "Please select a file or directory", "yellow");
        }
      });

      $(".dropdown .new-dir").click(function() {
        var path = $("#path").html();

        if (path.length > 0) {
          var name = prompt("Please enter directory name:", "new-dir"),
            end = path.substring(path.length - 1),
            dir = "";

          if (name != null && name.length > 0) {
            if (end == "/") {
              dir = path + name;
            } else {
              dir = path.substring(0, path.lastIndexOf("/") + 1) + name;
            }

            $.post("<?= $_SERVER['PHP_SELF'] ?>", {
              action: "make-dir",
              dir: dir
            }, function(data) {
              alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");

              if (data.error == false) {
                reloadFiles();
              }
            });
          }
        } else {
          alertBox("Warning", "Please select a file or directory", "yellow");
        }
      });

      $(".dropdown .save").click(function() {
        var path = $("#path").html(),
          data = editor.getValue();

        if (path.length > 0) {
          $("#digest").val(sha512(data));

          $.post("<?= $_SERVER['PHP_SELF'] ?>", {
            action: "save",
            file: path,
            data: data
          }, function(data) {
            alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");
          });
        } else {
          alertBox("Warning", "Please select a file", "yellow");
        }
      });

      $(".dropdown .close").click(function() {
        editor.setValue("");
        $("#files > div a:first").click();
        $(".dropdown").find(".save, .delete, .rename, .reopen, .close").addClass("disabled");
      });

      $(".dropdown .delete").click(function() {
        var path = $("#path").html();

        if (path.length > 0) {
          if (confirm("Are you sure to delete this file?")) {
            $.post("<?= $_SERVER['PHP_SELF'] ?>", {
              action: "delete",
              path: path
            }, function(data) {
              alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");

              if (data.error == false) {
                reloadFiles();
              }
            });
          }
        } else {
          alertBox("Warning", "Please select a file or directory", "yellow");
        }
      });

      $(".dropdown .rename").click(function() {
        var path = $("#path").html(),
          split = path.split("/"),
          file = split[split.length - 1],
          dir = split[split.length - 2],
          new_file_name;

        if (path.length > 0) {
          if (file.length > 0) {
            new_file_name = file;
          } else if (dir.length > 0) {
            new_file_name = dir;
          } else {
            new_file_name = "new-file";
          }

          var name = prompt("Please enter new name:", new_file_name);

          if (name != null && name.length > 0) {
            $.post("<?= $_SERVER['PHP_SELF'] ?>", {
              action: "rename",
              path: path,
              name: name
            }, function(data) {
              alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");

              if (data.error == false) {
                reloadFiles(path.substring(0, path.lastIndexOf("/")) + "/" + name);
              }
            });
          }
        } else {
          alertBox("Warning", "Please select a file or directory", "yellow");
        }
      });

      $(".dropdown .reopen").click(function() {
        var path = $("#path").html();

        if (path.length > 0) {
          $(window).trigger("hashchange");
        }
      });

      $(window).resize(function() {
        if (window.innerWidth >= 720) {
          var terminalHeight = $("#terminal").length > 0 ? $("#terminal").height() : 0,
            height = window.innerHeight - $(".CodeMirror")[0].getBoundingClientRect().top - terminalHeight - 30;

          $("#files, .CodeMirror").css({
            "height": height + "px"
          });
        } else {
          $("#files > div, .CodeMirror").css({
            "height": ""
          });
        }

        if (document.fullscreen) {
          $("#prompt pre").height($(window).height() - $("#prompt input.command").height() - 20);
        }
      });

      $(window).resize();

      $(document).bind("keyup", function(event) {
        if ((event.ctrlKey || event.metaKey) && event.shiftKey) {
          if (event.keyCode == 78) {
            $(".dropdown .new-file").click();
            event.preventDefault();

            return false;
          } else if (event.keyCode == 83) {
            $(".dropdown .save").click();
            event.preventDefault();

            return false;
          } else if (event.keyCode == 76) {
            $("#terminal .toggle").click();
            event.preventDefault();

            return false;
          }
        }
      });

      $(document).bind("keyup", function(event) {
        if (event.keyCode == 27) {
          if (last_keyup_press == true) {
            last_keyup_double = true;

            $("#fileMenu").click();
            $("body").focus();
          } else {
            last_keyup_press = true;

            setTimeout(function() {
              if (last_keyup_double === false) {
                if (document.activeElement.tagName.toLowerCase() == "textarea") {
                  if ($("#terminal #prompt").hasClass("show")) {
                    $("#terminal .command").focus();
                  } else {
                    $(".jstree-clicked").focus();
                  }
                } else if (document.activeElement.tagName.toLowerCase() == "input") {
                  $(".jstree-clicked").focus();
                } else {
                  editor.focus();
                }
              }

              last_keyup_press = false;
              last_keyup_double = false;
            }, 250);
          }
        }
      });

      $(window).on("hashchange", function() {
        var hash = window.location.hash.substring(1),
          data = editor.getValue();

        if (hash.length > 0) {
          if ($("#digest").val().length < 1 || $("#digest").val() == sha512(data)) {
            if (hash.substring(hash.length - 1) == "/") {
              var dir = $("a[data-dir='" + hash + "']");

              if (dir.length > 0) {
                editor.setValue("");
                $("#digest").val("");
                $("#path").html(hash);
                $(".dropdown").find(".save, .reopen, .close").addClass("disabled");
                $(".dropdown").find(".delete, .rename").removeClass("disabled");
              }
            } else {
              var file = $("a[data-file='" + hash + "']");

              if (file.length > 0) {
                $("#loading").fadeIn(250);

                $.post("<?= $_SERVER['PHP_SELF'] ?>", {
                  action: "open",
                  file: encodeURIComponent(hash)
                }, function(data) {
                  if (data.error == true) {
                    alertBox("Error", data.message, "red");

                    return false;
                  }

                  editor.setValue(data.data);
                  editor.setOption("mode", "application/x-httpd-php");

                  $("#digest").val(sha512(data.data));

                  if (hash.lastIndexOf(".") > 0) {
                    var extension = hash.substring(hash.lastIndexOf(".") + 1);

                    if (modes[extension]) {
                      editor.setOption("mode", modes[extension]);
                    }
                  }

                  $("#editor").attr("data-file", hash);
                  $("#path").html(hash).hide().fadeIn(250);
                  $(".dropdown").find(".save, .delete, .rename, .reopen, .close").removeClass("disabled");

                  $("#loading").fadeOut(250);
                });
              }
            }
          } else if (confirm("Discard changes?")) {
            $("#digest").val("");

            $(window).trigger("hashchange");
          }
        }
      });

      if (window.location.hash.length < 1) {
        window.location.hash = "/";
      } else {
        $(window).trigger("hashchange");
      }

      $("#files").on("click", ".jstree-anchor", function() {
        location.href = $(this).attr("href");
      });

      $(document).ajaxError(function(event, request, settings) {
        var message = "An error occurred with this request.";

        if (request.responseText.length > 0) {
          message = request.responseText;
        }

        if (confirm(message + " Do you want to reload the page?")) {
          location.reload();
        }

        $("#loading").fadeOut(250);
      });

      $(window).keydown(function(event) {
        if ($("#fileMenu[aria-expanded='true']").length > 0) {
          var code = event.keyCode;

          if (code == 78) {
            $(".new-file").click();
          } else if (code == 83) {
            $(".save").click();
          } else if (code == 68) {
            $(".delete").click();
          } else if (code == 82) {
            $(".rename").click();
          } else if (code == 79) {
            $(".reopen").click();
          } else if (code == 67) {
            $(".close").click();
          } else if (code == 85) {
            $(".upload-file").click();
          }
        }
      });

      $(".dropdown .upload-file").click(function() {
        $("#uploadFileModal").modal("show");
        $("#uploadFileModal input").focus();
      });

      $("#uploadFileModal button").click(function() {
        var form = $(this).closest("form"),
          formdata = false;

        form.find("input[name=destination]").val(window.location.hash.substring(1));

        if (window.FormData) {
          formdata = new FormData(form[0]);
        }

        $.ajax({
          url: "<?= $_SERVER['PHP_SELF'] ?>",
          data: formdata ? formdata : form.serialize(),
          cache: false,
          contentType: false,
          processData: false,
          type: "POST",
          success: function(data, textStatus, jqXHR) {
            alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");

            if (data.error == false) {
              reloadFiles();
            }
          }
        });
      });

      var terminal_dir = "";

      $("#terminal .command").keydown(function(event) {
        if (event.keyCode == 13) {
          if ($(this).val().length > 0) {
            var _this = $(this)
            _val = _this.val();

            if (_val.toLowerCase() == "clear") {
              $("#terminal pre").html("");
              _this.val("").focus();

              return true;
            }

            _this.prop("disabled", true);
            $("#terminal pre").append("<span class=\"command\">&gt; " + _val + "</span>\n");
            $("#terminal pre").animate({
              scrollTop: $("#terminal pre").prop("scrollHeight")
            });

            var terminal_commands = $.parseJSON(getCookie("terminal_commands"));

            if (terminal_commands === false) {
              terminal_commands = [];
            }

            terminal_commands.push(_val);

            if (terminal_commands.length > 50) {
              terminal_commands = terminal_commands.slice(1);
            }

            setCookie("terminal_commands", JSON.stringify(terminal_commands));

            $.post("<?= $_SERVER['PHP_SELF'] ?>", {
              action: "terminal",
              command: _val,
              dir: terminal_dir
            }, function(data) {
              if (data.error) {
                $("#terminal pre").append(data.message);
              } else {
                if (data.dir != null) {
                  terminal_dir = data.dir;
                }

                if (data.result == null) {
                  data.result = "Command not found\n";
                }

                $("#terminal pre").append(data.result);
              }

              $("#terminal pre").stop().animate({
                scrollTop: $("#terminal pre").prop("scrollHeight")
              });
              _this.val("").prop("disabled", false).focus();
            });
          } else {
            $("#terminal pre").append("\n");
            $("#terminal pre").stop().animate({
              scrollTop: $("#terminal pre").prop("scrollHeight")
            });
          }
        } else if (event.keyCode == 38) {
          var terminal_commands = $.parseJSON(getCookie("terminal_commands"));

          if (terminal_commands && terminal_commands[terminal_commands.length - terminal_history]) {
            $(this).val(terminal_commands[terminal_commands.length - terminal_history]);

            terminal_history += 1;
          }
        } else if (event.keyCode == 40) {
          if (terminal_history > 1) {
            var terminal_commands = $.parseJSON(getCookie("terminal_commands"));

            if (terminal_commands && terminal_commands[terminal_commands.length - terminal_history + 2]) {
              $(this).val(terminal_commands[terminal_commands.length - terminal_history + 2]);

              terminal_history -= 1;
            }
          }
        }
      });

      $("#terminal .toggle").click(function() {
        if ($(this).attr("aria-expanded") != "true") {
          $("#terminal .command").focus();
        }
      });

      $('#prompt').on('show.bs.collapse', function() {
        $("#terminal").find(".clear, .copy, .fullscreen").css({
          "display": "block",
          "opacity": "0",
          "margin-right": "-30px"
        }).animate({
          "opacity": "1",
          "margin-right": "0px"
        }, 250);

        if (window.innerWidth >= 720) {
          var height = window.innerHeight - $(".CodeMirror")[0].getBoundingClientRect().top - $("#terminal #prompt").height() - 55;

          $("#files, .CodeMirror").animate({
            "height": height + "px"
          }, 250);
        } else {
          $("#files > div, .CodeMirror").animate({
            "height": ""
          }, 250);
        }

        setCookie("terminal", "1", 86400);
      }).on('hide.bs.collapse', function() {
        $("#terminal").find(".clear, .copy, .fullscreen").fadeOut();

        if (window.innerWidth >= 720) {
          var height = window.innerHeight - $(".CodeMirror")[0].getBoundingClientRect().top - $("#terminal span").height() - 35;

          $("#files, .CodeMirror").animate({
            "height": height + "px"
          }, 250);
        } else {
          $("#files > div, .CodeMirror").animate({
            "height": ""
          }, 250);
        }

        setCookie("terminal", "0", 86400);
      }).on('shown.bs.collapse', function() {
        $("#terminal .command").focus();
      });

      $("#terminal button.clear").click(function() {
        $("#terminal pre").html("");
        $("#terminal .command").val("").focus();
      });

      $("#terminal button.copy").click(function() {
        $("#terminal").append($("<textarea>").html($("#terminal pre").html()));

        element = $("#terminal textarea")[0];
        element.select();
        element.setSelectionRange(0, 99999);
        document.execCommand("copy");

        $("#terminal textarea").remove();
      });

      if (getCookie("terminal") == "1") {
        $("#terminal .toggle").click();
      }

      $("#terminal .fullscreen").click(function() {
        var element = $("#terminal #prompt")[0];

        if (element.requestFullscreen) {
          element.requestFullscreen();

          setTimeout(function() {
            $("#prompt pre").height($(window).height() - $("#prompt input.command").height() - 20);
            $("#prompt input.command").focus();
          }, 500);
        }
      });

      $(window).on("fullscreenchange", function() {
        if (document.fullscreenElement == null) {
          $("#terminal #prompt pre").css("height", "");
          $(window).resize();
        }
      });

      $(".server-function").click(function() {
        $("#loading-background").removeClass("hidden");
      });

    });
  // End Text editor code
  </script>
  <?php 
  // End If //
  };?>
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
                <div class="col-<?php echo $text_row;?> h6"><span class="glyphicon glyphicon-hdd" aria-hidden="true"></span> <?php echo $active; ?></div>
                <div class="col-4 <?php echo $url_copy;?>">
                      <button id="copyButton" title="Click to copy" class="btn input-group-addon btn-<?php echo $alert_class;?>" <?php echo $alert_attr;?>><span class="glyphicon glyphicon-copy"></span></button>
                      <input type="text" id="copyTarget" class="form-control" value="<?php echo $realIP . ':' . $port;?>">
                </div>
                <?php
                if ($make_seed_public == true && $has_seed == true ) { ?>
                <div class="col-1">
                  <button class="btn btn-success view-world" onclick="window.open('https://valheim-map.world/?seed=<?php echo $seed; ?>&offset=506%2C778&zoom=0.077&view=0&ver=0.148.6')"><span class="glyphicon glyphicon-globe"></span></button>
                </div>
                <?php } ?>
              </div>
        <?php
        if ($mod_file_count > 0 && $show_mods == true) {
        ?>
          <div class="row">
            <div class="col-12">
              <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="false">
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

        // Version Control
        $url = "https://raw.githubusercontent.com/Peabo83/Valheim-Server-Web-GUI/main/.gitignore/version";
        $latest_version = file_get_contents($url);
        $latest_version = strtok($latest_version, "\n");
        if ($version == $latest_version) {
          // DO NOTHING
        } else {
          echo "<div class='row alert alert-danger' role='alert'><div class='col-12'><span class='glyphicon glyphicon-warning-sign'></span> Your version of this GUI is out out of date. (current version: ".$version." - latest version:<a href='https://github.com/Peabo83/Valheim-Server-Web-GUI'>".$latest_version."</a>)</div></div>";
        }
        // End Version Control
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
            <label class="label label-info">Public</label> <?php echo $public_status; ?>
            <label class="label label-info">Seed</label> <?php echo $seed; ?><br><br>
            <button class="btn btn-danger server-function" onclick="location.href='index.php?stop=true';" <?php echo $public_attr;?>>Stop</button> 
            <button class="btn btn-success server-function" onclick="location.href='index.php?start=true';" <?php echo $start_attr;?>>Start</button> 
            <button class="btn btn-warning server-function" onclick="location.href='index.php?restart=true';" <?php echo $public_attr;?>>Restart</button> 
            <button class="btn btn-<?php echo $no_download_class;?>" <?php echo $no_download; ?> onclick="location.href='index.php?download_db=true';">Download DB</button> 
            <button class="btn btn-<?php echo $no_download_class;?>" onclick="location.href='index.php?download_fwl=true';" <?php echo $no_download; ?>>Download FWL</button> <a class="btn btn-primary" href="?logout=true">Logout</a>
            <?php if ($server_log == true) { ?>
              <div class="panel-group" id="accordion2" role="serverlog" aria-multiselectable="true">
                <div class="panel panel-default">
                  <div class="panel-heading" role="tab" id="serverlogs">
                    <h4 class="panel-title">
                      <a role="button" data-toggle="collapse" data-parent="#accordion2" href="#serverlogbody" aria-expanded="false" aria-controls="serverlogbody" class="">
                        Server Logs
                      </a>
                    </h4>
                  </div>
                  <div id="serverlogbody" class="panel-collapse collapse" role="logpanel" aria-labelledby="serverlogs">
                    <div class="panel-body">
                      <?php
                        $log = shell_exec('sudo grep "Got connection SteamID\|Closing socket\|has wrong password\|Got character ZDOID from\|World saved" /var/log/syslog');
                        $log_array = explode("\n", $log);
                        foreach ($log_array as $key => $value) {
                          echo $value . "<br>";
                        }
                      ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
      <?php if ($admins_and_bans == true) { ?>
      <div class="panel panel-primary">
        <div class="panel-heading" role="tab" id="headingFour">
          <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
              Admins & Bans
            </a>
          </h4>
        </div>
        <div id="collapseFour" class="panel-collapse <?php echo $server_accordion;?>" role="tabpanel" aria-labelledby="headingFour">
          <div class="panel-body">
            <div class="row" style="flex-direction: row-reverse;">
              <div class="col-md-4">
                <div class="thumbnail">
                  <?php
                  // Location of banlist.txt
                  $file = fopen("/home/steam/.config/unity3d/IronGate/Valheim/bannedlist.txt", "r");
                  $already_shown = array();
                  $extra_IDs_to_show = array();
                  //Output lines until EOF reached
                  while(! feof($file)) {
                      $line = fgets($file);
                      $line = str_replace("// List banned players ID  ONE per line", "Bans", $line);
                      if (strpos($line, 'Bans') !== false) {
                        echo "<div class='row'><div class='col-md-12'>" .$line . "</div></div>";
                      } else {
                        $clean_line = strtok($line, "\n");
                        if (!empty($line) && $clean_line != "" ) {
                          $extra_IDs_to_show[] = $line;
                          $url = "https://steamidfinder.com/lookup/" . $line;
                          $fp = file_get_contents($url);
                          $res = preg_match("/<title>(.*)<\/title>/siU", $fp, $title_matches);
                          $title_array = explode(" ", $title_matches[1]);
                          if ($title_array[0] == "steam" || $title_array[0] == "404" || $title_array[0] == "") {
                            echo "<div class='row AnB_item'><div class='col-md-10'><a target=_blank href='https://steamidfinder.com/lookup/" . $clean_line . "'>Error:ID not Found</a></div><div class='col-md-2'><button class='btn btn-danger btn-xs' onclick=\"location.href='index.php?remove_ban=".$clean_line."'\" ><span class='glyphicon glyphicon-trash'></span></button></div></div>";
                          } else {
                            echo "<div class='row AnB_item'><div class='col-md-10'><a data-toggle='tooltip' data-placement='top' title='ID: ".$line."' target=_blank href='https://steamidfinder.com/lookup/" . $clean_line . "'>".$title_array[0]."</a></div><div class='col-md-2'><button class='btn btn-danger btn-xs' onclick=\"location.href='index.php?remove_ban=".$clean_line."'\" ><span class='glyphicon glyphicon-trash'></span></button></div></div>";
                          }
                        }
                      }
                  }
                  fclose($file);
                  ?>
                </div>
              </div>
              <div class="col-md-4">
                <div class="thumbnail">
                  <?php
                  // Location of adminlist.txt
                  $file = fopen("/home/steam/.config/unity3d/IronGate/Valheim/adminlist.txt", "r");
                  //Output lines until EOF reached
                  while(! feof($file)) {
                      $line = fgets($file);
                      $line = str_replace("// List admin players ID  ONE per line", "Admins", $line);
                      if (strpos($line, 'Admins') !== false) {
                        echo "<div class='row'><div class='col-md-12'>" .$line . "</div></div>";
                      } else {
                        $clean_line = strtok($line, "\n");
                        if (!empty($line) && $clean_line != "" ) {
                          $extra_IDs_to_show[] = $line;
                          $url = "https://steamidfinder.com/lookup/" . $line;
                          $fp = file_get_contents($url);
                          $res = preg_match("/<title>(.*)<\/title>/siU", $fp, $title_matches);
                          $title_array = explode(" ", $title_matches[1]);
                          if ($title_array[0] == "steam" || $title_array[0] == "404" || $title_array[0] == "") {
                            echo "<div class='row AnB_item'><div class='col-md-10'><a target=_blank href='https://steamidfinder.com/lookup/" . $line . "'>Error: ID not Found</a></div><div class='col-md-2'><button class='btn btn-danger btn-xs' onclick=\"location.href='index.php?remove_admin=".$clean_line."'\" ><span class='glyphicon glyphicon-trash'></span></button></div></div>";
                          } else {
                          echo "<div class='row AnB_item'><div class='col-md-10'><a data-toggle='tooltip' data-placement='top' title='ID: ".$line."' target=_blank href='https://steamidfinder.com/lookup/" . $line . "'>".$title_array[0]."</a></div><div class='col-md-2'><button class='btn btn-danger btn-xs' onclick=\"location.href='index.php?remove_admin=".$clean_line."'\" ><span class='glyphicon glyphicon-trash'></span></button></div></div>";
                          }
                        }
                      }
                  }
                  fclose($file);
                  ?>
                </div>
              </div>
              <div class="col-md-4">
                <div class="thumbnail">
                  <div class="row">
                    <div class="col-md-8">
                      Recent Players
                    </div>
                    <div class="col-md-4">
                      add to:
                    </div>
                  </div>
                  <?php

                    $recent_players = shell_exec('sudo grep handshake /var/log/syslog');
                    $recent_players = nl2br($recent_players);
                    $recent_players_array = explode('<br />', $recent_players);
                    foreach ($recent_players_array as $key => $value) {

                      $steam_long_id = substr($value, strpos($value, "client") + 6);
                      $steam_long_id = str_replace(' ', '', $steam_long_id);

                      if (!in_array($steam_long_id, $already_shown) && !empty($steam_long_id && $steam_long_id != "") ) {
                        $url = "https://steamidfinder.com/lookup/" . $steam_long_id;
                        $fp = file_get_contents($url);
                        $res = preg_match("/<title>(.*)<\/title>/siU", $fp, $title_matches);
                        $title_array = explode(" ", $title_matches[1]);
                        echo "<div class='row AnB_item'>
                                <div class='col-md-7'>
                                  <a data-toggle='tooltip' data-placement='top' title='ID: ".$steam_long_id."' target=_blank href='https://steamidfinder.com/lookup/" . $steam_long_id . "'>" . $title_array[0] . "</a>
                                </div>
                                <div class='col-md-3'>
                                  <button class='btn btn-success btn-xs' onclick=\"location.href='index.php?add_admin=".$steam_long_id."';\" >Admin</button>
                                </div>
                                <div class='col-md-2'>
                                  <button class='btn btn-danger btn-xs' onclick=\"location.href='index.php?add_ban=".$steam_long_id."';\">Ban</button>
                                </div>
                              </div>";
                        $already_shown[] = strip_tags($steam_long_id);
                      }
                    }

                    foreach ($extra_IDs_to_show as $key => $value) {
                      $steam_long_id = preg_replace("/[^0-9]/", "", $value );
                      if (!in_array($steam_long_id, $already_shown) && !empty($steam_long_id && $steam_long_id != "") ) {
                        $url = "https://steamidfinder.com/lookup/" . $steam_long_id;
                        $fp = file_get_contents($url);
                        $res = preg_match("/<title>(.*)<\/title>/siU", $fp, $title_matches);
                        $title_array = explode(" ", $title_matches[1]);
                        echo "<div class='row AnB_item'>
                                <div class='col-md-7'>
                                  <a data-toggle='tooltip' data-placement='top' title='ID: ".$steam_long_id."' target=_blank href='https://steamidfinder.com/lookup/" . $steam_long_id . "'>" . $title_array[0] . "</a>
                                </div>
                                <div class='col-md-3'>
                                  <button class='btn btn-success btn-xs' onclick=\"location.href='index.php?add_admin=".$steam_long_id."';\" >Admin</button>
                                </div>
                                <div class='col-md-2'>
                                  <button class='btn btn-danger btn-xs' onclick=\"location.href='index.php?add_ban=".$steam_long_id."';\">Ban</button>
                                </div>
                              </div>";
                        $already_shown[] = $steam_long_id;
                      }
                    }


                  ?>
                </div>
              </div>
          </div>
          <?php
            if (isset($Verified_ID)) {
              if ($Verified_ID == 'UNVERIFIED') {
                echo "<div class='row'>
                        <div class='col-12'>
                          <div class='alert alert-danger' role='alert'>
                            <span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true'></span>
                            <span class='sr-only'>Error:</span>
                            Unable to validate entered ID. See <a href='https://steamidfinder.com/'>SteamIDFinder.com</a> for getting someones steamID64(Dec).
                          </div>
                        </div>
                      </div>";
              } else {
                $url = "https://steamidfinder.com/lookup/" . $Verified_ID;
                $fp = file_get_contents($url);
                $res = preg_match("/<title>(.*)<\/title>/siU", $fp, $title_matches);
                $title_array = explode(" ", $title_matches[1]);
                $name = $title_array[0];
                echo "<div class='row'>
                        <div class='col-12'>
                          <div class='alert alert-success' role='alert'>
                            <span class='glyphicon glyphicon-ok' aria-hidden='true'></span>
                            <span class='sr-only'>Success:</span>
                            Verified ID: <a href='https://steamidfinder.com/lookup/".$Verified_ID."'>".$name."</a> <button class='btn btn-success btn-xs' onclick=\"location.href='index.php?add_admin=".$Verified_ID."';\" >Admin</button> <button class='btn btn-danger btn-xs' onclick=\"location.href='index.php?add_ban=".$Verified_ID."';\">Ban</button>
                          </div>
                        </div>
                      </div>";
              }
            }
          ?>
          <div class="row">
            <div class="col-12">
              <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
              <div class="input-group">
                <input type="text" name="ID_to_Verify" class="form-control" placeholder="Validate a steamID64(Dec) to add to Admin/Ban List">
                <input type="submit" class="btn btn-success" class="form-control">
              </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php 
      // End Admins & Bans Panel
      }
      // Start MOD CFG Editor
      if ($mod_file_count > 0 && $cfg_editor == true) {
      ?>
      <div class="panel panel-primary">
        <div class="panel-heading" role="tab" id="headingThree">
          <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
              Mod CFG Editor
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
      <?php } // End if ?>
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
    display_login_form();
  }
  function display_login_form() { ?>
    <form action="<?php echo $self; ?>" method='post'>
    <div class="row login">
          <div class="col-5"><input type="text" name="username" id="username" class="form-control"></div>
          <div class="col-5"><input type="password" name="password" id="password" class="form-control"></div>
          <div class="col-2"><input class="btn btn-success" type="submit" name="submit" value="submit" style="width: 100%;"></div>
          <div style="display: none;">
          <textarea id="editor" data-file="" class="form-control"></textarea>
          <input id="digest" type="hidden" readonly>
          </div>
        </form>
    </div>
  <?php } ?>
  </body>
</html>
