#!/bin/bash

TOOL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
PHPSTAN_DIR="$TOOL_DIR/../../../vendor/bin/phpstan"
CONFIG_FILE="$TOOL_DIR/phpstan.neon"

# Verify folder
if [ ! -f "$PHPSTAN_DIR" ]; then
    echo "Error: PHPStan not finded in $PHPSTAN_DIR"
    exit 1
fi

# Verify file
if [ ! -f "$CONFIG_FILE" ]; then
    echo "Error: The configuration file doesn't exist in $CONFIG_FILE"
    exit 1
fi

# Execute PHPStan
$PHPSTAN_DIR analyse -c "$CONFIG_FILE"
