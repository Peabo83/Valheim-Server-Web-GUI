# Valheim-Server-Web-GUI

## Features
- Web page that publicly shows the status of valheimserver.service
- Has a public facing Copy to clipboard button for easy pasting into Valheim
- Looks at your /BepInEx/config folder and can publicly display mods installed with a link to their Nexus page or display a defined list of mods
- Can show (publicly or not) the Seed of the running world with a custom link to http://valheim-map.world/
### When Logged in
- Gives you the ability to edit the CFG mod files with an in-browser editor
- Turn off/on the valheimserver.service process
- Download a copy of your .DB and .FWL files

## Credits
Simple no database login from https://gist.github.com/thagxt/94b976db4c8f14ec1527<br>
In-browser editor code from https://github.com/pheditor/pheditor<br>
This would not work without https://github.com/Nimdy/Dedicated_Valheim_Server_Script

## Screenshots
![alt text](https://i.imgur.com/sDE7T2x.jpg)<br>
<br>
![alt text](https://i.imgur.com/ELlf5TM.jpg)<br>
<br>
![alt text](https://i.imgur.com/6FBXr8L.jpg)<br>

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
	$show_mods = true;
	$cfg_editor = false;
	$make_seed_public = false;
```
Change $username and $password to your preffered values. Change $random1 and $random2 to any variables of your choice, like 'Valheim365' and 'OdinRules'.

5) To execute systemctl commands the PHP user (www-data) needs to be able to run systemctl commands, which by default it can not. The following will allow www-data to run the specific commands used to make the GUI work.

```
sudo visudo
```
This will open your sudo file, add the following at the bottom:

```
# Valheim web server commands
www-data ALL = (root) NOPASSWD: /bin/systemctl restart valheimserver.service
www-data ALL = (root) NOPASSWD: /bin/systemctl start valheimserver.service
www-data ALL = (root) NOPASSWD: /bin/systemctl stop valheimserver.service
www-data ALL = (root) NOPASSWD: /bin/cp -R /home/steam/.config/unity3d/IronGate/Valheim/worlds/* /var/www/html/download/
```

Then hit <kbd>CTRL</kbd> + <kbd>X</kbd> to exit VI, you will be prompted to save, so press <kbd>Y</kbd> and then <kbd>Enter</kbd>. VI will then ask where to save a .tmp file, just hit <kbd>Enter</kbd> again. After you save the .tmp visudo will check the file for errors, if there are none it will push the content to the live file automatically.


6) **Optional** - Enable the CFG Editor. Using your preferred text editor open /var/www/VSW-GUI-CONFIG and toggle the value of $cfg_editor from false to true, as so:

```
$cfg_editor = 'true';
```

VIA a terminal window set new permissions on the BepInEx/config folder and the the files in it.
```
sudo chmod -R 664 /home/steam/valheimserver/BepInEx/config/
sudo chmod 755 /home/steam/valheimserver/BepInEx/config/
```

Set the default of new files in the folder to the correct permissions.
```
sudo chmod g+s /home/steam/valheimserver/BepInEx/config/
sudo setfacl -d -m g::rwx /home/steam/valheimserver/BepInEx/config/
```

Then add www-data to the steam group.
```
sudo usermod -a -G steam www-data
```

Now reboot the server to ensure these new settings are in effect.
```
sudo reboot now
```

Once the reboot is complete reload your browser and (when logged in) you will see a new tab named Mod CFG Editor that can be used to edit your CFG files.  

## Making Mods Show up on the Public list of Mods

### Find a mods nexusID
A mod's nexusID appears in the URL of the mod, for example the URL for ValheimPlus is: https://www.nexusmods.com/valheim/mods/4 - That number at the end is the ID, so in the case of ValheimPlus the nexusID is 4. To have this mod display in the list you would add the following to valheim_plus.cfg:

```
NexusID = 4
```

### Add NexusID to the CFG File
Some mods will work automatically, but some will not. If you have a mod installed and it's not displaying you will need to add the mods nexus ID to it's CFG file like this:

```
NexusID = ###
```

Please note that this formatting must be exact, including the spaces around the equals sign.


### Manually add mods to the displayed list
Using your preferred text editor open /var/www/VSW-GUI-CONFIG, you will see the following option:

```
// Manually add nexus mods to displayed mods insert mod IDs comma delineated as such:
// $manual_add_displayed_mods = array('4', '189', '387');
$manual_add_displayed_mods = array();
```
The inline comments describe how to manually add a displayed Nexus mod, simply add the NexusID value in array() as shown in the example.

## To-do
Add additional error checking on systemctl commands<br>
Add log output to server section w/ live update via jquery<br>
Set Valheim Admins via a new panel<br>
Upload .zip to be uncompressed and imported to /BepInEx/plugins<br>
