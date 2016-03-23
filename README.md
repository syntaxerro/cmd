# Syntaxerro CMD Tools
Command line administrator tools written in PHP.

### Features:
- Create vhost for apache configuration from template.
```
./app.php http:add example.com
```

- Configuration format for nginx.
```
./app.php http:add example.com --nginx
```

- Create vhost with SSL support and auto redirection.
```
./app.php http:add example.com --ssl
```

- Cat all rotated logs in directory.
```
./app.php rotate:cat /var/log/nginx example.com
```
