# Valheim-Server-Web-GUI

# This repository is still a work in progress

This is a 'no database' web GUI built on Nimdy's Dedicated Valheim server script ( https://github.com/Nimdy/Dedicated_Valheim_Server_Script )

## Features

- Web page that publicly shows the status of valheimserver.service
- Has a Copy to clipboard button publicly for easy copypasta
- Looks at your /BepInEx/config folder and can publicly display mods installed with a link to their Nexus page (some mods may require a CFG edit to display)
### When Logged in
- Gives you the ability to edit the CFG mod files with an in-browser editor
- Turn off/on the valheimserver.service process from a browser
- Download a copy of your .DB and .FWL files

## Credits

Simple no database login from https://gist.github.com/thagxt/94b976db4c8f14ec1527<br>
In-browser editor code from https://github.com/pheditor/pheditor

## Screenshots

![alt text](https://imgur.com/Vw43muw)

## Install instructions
These instrcutions assume you are working on Ubuntu server as outlined by Nimdy.

1) Follow Nimdy's instuctions for setting up and configuring your Valheim server ( https://github.com/Nimdy/Dedicated_Valheim_Server_Script#readme )

2) Install PHP and Apache2

```
sudo apt install php libapache2-mod-php
```

Verify that the install was successful by putting the IP of the server in your web browser. You should see the default Apache2 Ubuntu page. If you have connection issues with this default page, you should verify that HTTP is enabled on the VM.

Note: If you click the little open arrow in the GCP VM management panel next to the server IP it will go to http<b><u>s</u></b>://your-IP, which will not work without further configuration.

3) Remove the default html folder from /var/www/ and then install repository to /var/www/

```
sudo rm -R /var/www/
cd ~
git clone https://github.com/Peabo83/Valheim-Server-Web-GUI.git
sudo cp -R ~/Valheim-Server-Web-GUI/www/ /var/
```

Now when visting the IP of the server you should see the main GUI screen.

4) Change the default username/password/hash keys. Using your preferred text editor open /var/www/VSW-GUI-CONFIG, you will see the inital section with the variables to change:
```
// *************************************** //
// *              VARIABLES              * //
// *************************************** //
	$username = 'Default_Admin';
	$password = 'ch4n93m3';
	$random1 = 'secret_key1';
	$random2 = 'secret_key2';
	$hash = md5($random1.$pass.$random2); 
	$self = $_SERVER['REQUEST_URI'];
	$cfg_editor = 'false';
```
Change $username and $password to your preffered values. Change $random1 and $random2 to any variables of your choice, like 'Valheim365' and 'OdinRules'.

5) This step requires improvement for security purposes. To execute server commands the PHP user (www-data) needs to be able to run systemctl commands, which by default it can not. Currently you can give www-data root access by editing the sudoer file.

```
sudo visudo
```
This will open your sudo file, add the following line at the bottom:

```
www-data                ALL=(ALL) NOPASSWD: ALL
```

The above entry requires revision for security purposes.

Then hit CTRL+X to exit VI, you will be prompted to save, so press Y and then Enter. VI will then ask where to save a .tmp file, just hit Enter again. After you save the .tmp visudo will check the file for errors, if there are none it will push the content to the live file automatically.


6) Optional - Enable the CFG Editor. Using your preferred text editor open /var/www/VSW-GUI-CONFIG and toggle the value of $cfg_editor from false to true, as so:

```
$cfg_editor = 'true';
```

Make the CFG files editable via the command:

```
sudo chmod -R 777 /home/steam/valheimserver/BepInEx/config
```

## Making Mods Show up on the Public list of Mods

Some mods will work automatically, but some will not. If you have a mod installed and it's not displaying you will need to add the mods nexus ID to it's CFG file like this:

```
NexusID = ###
```

Please note that this formatting must be exact, including the spaces around the equals sign.

A mod's NexusID appears in the URL of the mod, for example the URL for ValheimPlus is: https://www.nexusmods.com/valheim/mods/4 - That number at the end is the ID, so in the case of ValheimPlus the nexus ID is 4. To have this mod display in the list you would add the following to valheim_plus.cfg:

```
NexusID = 4
```
