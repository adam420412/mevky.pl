#!/bin/bash
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p .runtime
exec ./node_modules/.bin/wp-playground-cli server \
 --port=8080 --site-url=http://localhost:8080 --workers=6 --wp=7.1 --php=8.3 \
 --wordpress-install-mode=install-from-existing-files-if-needed \
 --mount-before-install=./.runtime:/wordpress \
 --mount-before-install=../mevky:/wordpress/wp-content/themes/mevky \
 --mount-before-install=./mu-plugins:/wordpress/wp-content/mu-plugins \
 --mount-before-install=.:/mevky-local --blueprint=blueprint.json
