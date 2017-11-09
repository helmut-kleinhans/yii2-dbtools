@echo off

set SETTINGSPATH=C:\Users\Roman\AppData\Roaming\Kodi\userdata
set CONFIGPATH=%SETTINGSPATH%\guisettings.xml
set CONFIGBACKUPPATH=%SETTINGSPATH%\guisettings.xml.bak
set KODIPATH=C:\Program Files\Kodi\kodi.exe

copy "%CONFIGBACKUPPATH%" "%CONFIGPATH%"

"%KODIPATH%"

goto:eof