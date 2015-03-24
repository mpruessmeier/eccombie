eccombie
========

datatransfer processes for csv data to emoncms

project restarted at 14.03.2015

The process should pick up data packages that are dropped in a dropzone directory and carryall it to the right hangar (directory)
the dropzone is a ftp directory, where different processes in other places can drop the data packages over the internet.

A Carryall process pickes up that packeges and copy it to the right hangar.

To Do:
- Cause of the Software is old style (for my old monitoring system) it should be changed for working with emoncms.
- It should be possible to set service by emoncms module
- and by settings file (like emonhub)


To Decide:
- bringing data to emonhub or
- bringing data to emnoncms


Difficulties:
- what heppens, is data ar send a second time to emoncms ?

