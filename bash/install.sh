#!/usr/bin/env bash

git clone https://github.com/syntaxerro/cmd
cd cmd && composer install

echo "If you are in sudoers, you can install me globally. You want to do? [y/n]";
read YES_OR_NO;

if [[ "$YES_OR_NO" = "Y" || "$YES_OR_NO" = "y" ]]; then
    echo "Custom name for this app: ";
    read name;
    sudo ln -s `pwd`/app.php "/usr/local/bin/$name"
    echo "Installation complete. Created $name command.";
    exit;
fi

echo "Installation locally complete."
