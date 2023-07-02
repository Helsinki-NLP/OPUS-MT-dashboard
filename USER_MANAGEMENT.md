
# OPUS-MT dashboard - User Management

User accounts are stored in a simple SQLite database. Make sure that PHP is configured to enable sqlite via the [PHP Data Object interface](https://www.php.net/manual/en/book.pdo.php). This may require installing sqlite and the PDO driver:

```
sudo apt-get install sqlite php-sqlite3
```

User management also requires to send e-mails for verifying user accounts and also for resetting a password for an existing account. You need to make sure that the standard PHP function [mail](https://www.php.net/manual/en/function.mail.php) is set up properly on your system and mails can be send from that server. Below are notes about setting up an external SMTP server to avoid installing and running a local mail server on the local host.


## Setting up Gmail as an external SMTP server


* Create a Google account
* Generate an app password: https://support.google.com/accounts/answer/185833?hl=en
* Install MSMTP:

```
sudo apt-get install msmtp
```

* Configure msmtp:

Create `~/.msmtprc` with your e-mail credentials:

```
# Set default values for all following accounts.
defaults
auth           on
tls            on
tls_certcheck  off
tls_trust_file /etc/ssl/certs/ca-certificates.crt

# Gmail
account        gmail
host           smtp.gmail.com
port           587
from           my-gmail-account@gmail.com
user           my-gmail-account@gmail.com
password       the-generated-app-password

# Set a default account
account default : gmail
```

Test the configuration by sending a test e-mail. Create a text file `sample_email.txt` with something like

```
From: my-gmail-account@gmail.com
To: my-own-email@domain.com
Subject: testing msmtp

This email was sent using sendmail
```

Send this message using

```
cat sample_email.txt | msmtp --debug -a gmail my-own-email@domain.com
```

If this works, we can now move the configuration to some more central place to make it accessible for the web-server and PHP. The simplest way is to make it the global msmtp configuration and make the user that runs the web-server (e.g. `www-data`) the owner of the config file:

```
sudo cp -p ~/.msmtprc /etc/msmtprc
sudo chown www-data /etc/msmtprc
sudo chmod 600 /etc/msmtprc
```

Finally, we need to setup sendmail in PHP to use msmtp instead. For this, edit the `php.ini` file (depending on your system, e.g. in `/etc/php/7.4/apache2/php.ini`) and set the variable `sendmail_path` to:

```
sendmail_path = "/usr/bin/msmtp -a gmail -t"
```

Now restart the web server, e.g. Apache:

```
sudo apachectl restart
```

If all works well then you can send messages from PHP using `mail`. You can test something like

```
<?php
if (mail("my-own-email@domain.com","PHP mail test","This email was sent using PHP's mail function."))
    print "Email successfully sent";
else
    print "An error occured";
?>
```



Further links:

* https://www.digitalocean.com/community/tutorials/how-to-use-gmail-or-yahoo-with-php-mail-function
* https://stackoverflow.com/questions/14456673/sending-email-with-php-from-an-smtp-server
* using a Google business workspace / admin account: https://support.google.com/a/answer/60751?sjid=8381232116319456289-EU

