# Syntaxerro CMD Tools
Command line administrator tools written in PHP.

### Installation:
- Require git and composer for installation.
- Require PHP >=5.5 for execute.
- Create symlink as `/usr/local/bin/manager` if sudo.
- Copy and paste this command.

##### Local
```bash
curl -k https://cdn.rawgit.com/syntaxerro/cmd/master/bash/install.sh | bash
```

##### Global
```bash
curl -k https://cdn.rawgit.com/syntaxerro/cmd/master/bash/install.sh | sudo bash
```

### Features:
- Create apache vhost configuration from template [SyntaxErro/Resources/tpl/apache-vhost.twig](https://github.com/syntaxerro/cmd/blob/master/src/SyntaxErro/Resources/tpl/apache-vhost.twig).
```
./app.php http:add example.com
```

- Configuration format for nginx [SyntaxErro/Resources/tpl/nginx-vhost.twig](https://github.com/syntaxerro/cmd/blob/master/src/SyntaxErro/Resources/tpl/nginx-vhost.twig).
```
./app.php http:add example.com --nginx
```

- Custom configuration templates [SyntaxErro/Resources/tpl](https://github.com/syntaxerro/cmd/blob/master/src/SyntaxErro/Resources/tpl).
```
./app.php http:add example.com --template "custom-template-name.twig"
```

- Create vhost with SSL support and auto redirection for not supported SNI certificates.
```
./app.php http:add example.com --ssl
```

- Adding aliases, users or domains to postfix + dovecot database.
```
./app.php smtp:add [alias|user|domain]
```

- Removing aliases, users or domains to postfix + dovecot database.
```
./app.php smtp:rm [alias|user|domain]
```

- Change passwords of users in postfix + dovecot database.
```
./app.php smtp:pass user@example.com
```

- Custom queries to postfix, dovecot and spamassassin database [SyntaxErro/Resources/config/queries.yml](https://github.com/syntaxerro/cmd/blob/master/src/SyntaxErro/Resources/config/queries.yml).
```yml
# *** Adding new domain. ***
#
# Parameter: domain name, eg. "example.com"
new_domain: "INSERT INTO virtual_domains SET name='%s'"
```

- Creating default postfix, dovecot and spamassassin database schema.
```
./app.php smtp:database
```

- Add blacklist or whitelist items to SpamAssassin user preferences database.
```
./app.php spam:add [black|white]
```


- Removing blacklist or whitelist items from SpamAssassin user preferences database.
```
./app.php spam:rm [black|white]
```

- Cat all rotated logs in directory.
```
./app.php catr /var/log/nginx example.com
```
