GHist
=====

Small script for export history from Google Talk (GTalk) and Hangouts.

Supported export in text files and and ejabberd MySQL database.

Requirements
------------

* PHP >= 5.4
* Language 'English (US)' of gmail web interface

How to use
----------

1. Open gmail in your browser
2. Click in link **Create new label** and enter some text
3. Open folder with chats
4. Select all chains and add your new label for selected chains
5. Clone or download code of GHist
7. (optional) If you need to export in DB, uncomment line in **run.php**

 ```
 $outputAdapter = new Adapter\EjabberdMysql();
 ```

8. Run run.php with command

 ```
 php run.php
 ```

9. Answer the questions

Demo
----

[ASCII-video (v.1.0)](http://ascii.io/a/4991)
