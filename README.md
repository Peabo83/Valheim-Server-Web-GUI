# Valheim-Server-Web-GUI
This is a 'no database' web GUI built on Nimdy's Dedicated Valheim server script ( https://github.com/Nimdy/Dedicated_Valheim_Server_Script )
*Requires Apache2, PHP and PHP command 'shell_exec' enabled

## Install instructions
These instrcutions assume you are working on Ubuntu server.

1) Follow Nimdy's instuctions for setting up and configuring your Valheim server ( https://github.com/Nimdy/Dedicated_Valheim_Server_Script#readme )

2) Install PHP and Apache2

```
sudo apt install php libapache2-mod-php
```

Verify that the install was successful by putting the IP of the server in your web browser. You should see the default Apache2 Ubuntu page. If you have connection issues with this default page, you should verify that HTTP is enabled on the VM.

3) Remove the default index.html file from /var/www/html and then install repository to /var/www/html

```
cd ~
sudo rm /var/www/html/index.html
git clone https://github.com/Peabo83/Valheim-Server-Web-GUI.git
sudo cp ~/Valheim-Server-Web-GUI/index.php /var/www/html/
```

Now when visting the IP of the server you should see the main GUI screen.

4) Change the default username/password/hash keys. Using your preferred text editor open /var/www/html/index.php, you will see the inital section with the variables to change:
```
<?php

session_start();
// ********** Login Variables ********** //

$username = 'Default_Admin';
$password = 'ch4n93m3';

$random1 = 'secret_key1';
$random2 = 'secret_key2';

$hash = md5($random1.$pass.$random2); 

$self = $_SERVER['REQUEST_URI'];

?>
```
Change $username and $password to your preffered values. Change $random1 and $random2 to any variables of your choice, like 'Valheim365' and 'OdinRules'.
