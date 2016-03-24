# Syntaxerro CMD Tools
Command line administrator tools written in PHP.

### Features:
- Create apache vhost configuration from template [SyntaxErro/Resources/tpl/apache-vhost.twig](https://github.com/syntaxerro/cmd/blob/master/src/SyntaxErro/Resources/tpl/apache-vhost.twig).
```
./app.php http:add example.com
```

- Configuration format for nginx [SyntaxErro/Resources/tpl/nginx-vhost.twig](https://github.com/syntaxerro/cmd/blob/master/src/SyntaxErro/Resources/tpl/nginx-vhost.twig).
```
./app.php http:add example.com --nginx
```

- Create vhost with SSL support and auto redirection for not supported SNI certificates.
```
./app.php http:add example.com --ssl
```

- Cat all rotated logs in directory.
```
./app.php rotate:cat /var/log/nginx example.com
```

- Adding domains to postfix + dovecot database.
```
./app.php smtp:add domain
```

- Adding users to postfix + dovecot database.
```
./app.php smtp:add user
```

- Adding aliases to postfix + dovecot database.
```
./app.php smtp:add alias
```

- Custom queries to postfix and dovecot database [SyntaxErro/Resources/config/queries.yml](https://github.com/syntaxerro/cmd/blob/master/src/SyntaxErro/Resources/config/queries.yml).
```yml
# *** Adding new domain. ***
#
# Parameter: domain name, eg. "example.com"
new_domain: "INSERT INTO virtual_domains SET name='%s'"
```
