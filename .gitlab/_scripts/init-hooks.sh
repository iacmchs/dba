#!/usr/bin/env sh

if [ ! -f .git/hooks/commit-msg ]; then
    ln -s ../../.gitlab/_hooks/commit-msg .git/hooks
fi
if [ ! -f .git/hooks/pre-push ]; then
    ln -s ../../.gitlab/_hooks/pre-push .git/hooks
fi
