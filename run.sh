#!/bin/bash
cd "$(dirname "$0")/backend"
echo "==> Bangron Studio"
echo "API: http://localhost:8000"
echo "Vite: http://localhost:5173"
echo
php -S localhost:8000 -t public
