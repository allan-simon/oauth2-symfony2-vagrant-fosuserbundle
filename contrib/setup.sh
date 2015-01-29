#!/bin/sh

if [ ! -d ".git/hooks" ]; then
    mkdir .git/hooks
fi
cp contrib/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
