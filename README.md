# Convert WBB Lite to phpBB

## Installation

Copy the extension to phpBB/ext/FH3095/ConvertWbbLite

Go to "ACP" > "Customise" > "Extensions" and enable the "Convert WBB Lite to phpBB" extension.

## License

[GPLv2](license.txt)

## Usage

I used this only once and have no intention to develop it any further. Feel free to fork!

Install the extension, rename convertconfig.inc.php.example to convertconfig.inc.php and edit the file accordingly.<br>
You have to create the forums manually and put the ids into the convertconfig.inc.php file.<br>
In phpBB deactivate the option "Enable dotted topics" and "Enable server-side topic marking".<br>
Do the migration via the admin area.<br>
Dont forget to deactivate the addon afterwards. The addon deactivates two phpBB core features, so only activate it for migration.
