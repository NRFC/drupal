#!/usr/bin/env bash

# get bash colors and styles here:
# http://misc.flogisoft.com/bash/tip_colors_and_formatting
C_RESET='\e[0m'
C_RED='\e[31m'
C_GREEN='\e[32m'
C_YELLOW='\e[33m'


function __run() #(step, name, cmd)
{
    local color output exit_codecode

    printf "${C_YELLOW}[%s]${C_RESET} %-20s" "$1" "$2"
    output=$(eval "$3" 2>&1)
    exit_codecode=$?

    if [[ 0 == "$exit_codecode" || 130 == "$exit_codecode" ]]; then
        echo -e "${C_GREEN}OK!${C_RESET}"
    else
        echo -e "${C_RED}NOK!${C_RESET}\n\n$output"
        exit 1
    fi
}
