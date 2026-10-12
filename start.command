#!/bin/zsh
cd -- "${0:A:h}" || exit 1
exec /Applications/XAMPP/xamppfiles/bin/php -S 127.0.0.1:8000 -t public
