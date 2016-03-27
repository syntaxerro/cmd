#!/usr/bin/env bash

git clone https://github.com/syntaxerro/cmd
cd cmd && composer install

if [[ `whoami` = "root"  ]]; then
    ln -s `pwd`/app.php "/usr/local/bin/manager"
    echo "Installation complete. Created global 'manager' command.";
    exit;
fi

echo "Installation locally complete."
