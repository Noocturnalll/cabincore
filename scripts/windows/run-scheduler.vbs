' Runs `php artisan schedule:run` once, hidden, and appends the output to storage\logs\scheduler.log.
' Meant to be started every minute by Windows Task Scheduler (see deploy\README.md).
'
' PHP is taken from the CBM_PHP environment variable when set, otherwise `php` from PATH.

Set fso = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

' scripts\windows\run-scheduler.vbs  ->  project root is two folders up
root = fso.GetParentFolderName(fso.GetParentFolderName(fso.GetParentFolderName(WScript.ScriptFullName)))

php = shell.ExpandEnvironmentStrings("%CBM_PHP%")
If php = "%CBM_PHP%" Or php = "" Then php = "php"

shell.CurrentDirectory = root
' 0 = hidden window, False = do not wait
shell.Run "cmd /c """ & php & """ artisan schedule:run >> storage\logs\scheduler.log 2>&1", 0, False
