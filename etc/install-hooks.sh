#!/bin/bash

CWD="$(pwd)"
HOOK_DIR="$CWD/../.git/hooks"

ln -sf $CWD/pre-commit $HOOK_DIR
ln -sf $CWD/pre-push $HOOK_DIR
