#!/usr/bin/env bash

if ! type "git" > /dev/null 2>&1; then
  echo "Cannot install without git.";
  exit;
fi

if ! type "composer" > /dev/null 2>&1; then
  echo "Cannot install without composer.";
  exit;
fi

git clone https://github.com/syntaxerro/cmd
cd cmd && composer install

if [[ `whoami` = "root"  ]]; then
    ln -s `pwd`/app.php "/usr/local/bin/manager"
    echo "Installation complete. Created global 'manager' command.";
    exit;
fi

echo "Installation locally complete."
