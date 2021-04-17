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
