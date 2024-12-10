#!/bin/bash

SCRIPT_DIR=$(dirname "$(readlink -f "$0")")
PARENT_FOLDER_PATH=$(dirname "$SCRIPT_DIR")
PLUGINNAME=$(basename "$PARENT_FOLDER_PATH")

if [ ! "$#" -eq 1 ]; then
    echo "Usage $0 <Plugin name> (PascalCase)"
    exit
fi

PLUGINNAME_PASCAL=$1

# Check if string is in PascalCase, it must admit simple words like "Test"
if [[ ! $PLUGINNAME_PASCAL =~ ^([A-Z][a-z]*)+$ ]]; then
    echo "Plugin name must be in PascalCase"
    exit
fi

# To avoid class name conflicts we will use the plugin name in capital
# For example ActualTime in db creates glpi_plugin_actual_times tables
# we don't want this so the name must be Actualtime
PLUGINNAME_CAPIT=$(echo $PLUGINNAME_PASCAL | tr '[:upper:]' '[:lower:]' | sed 's/./\U&/')
PLUGINNAME_MINUS=$(echo $PLUGINNAME_PASCAL | tr '[:upper:]' '[:lower:]')
PLUGINNAME_MAYUS=$(echo $PLUGINNAME_PASCAL | tr '[:lower:]' '[:upper:]')

STRING_PASCAL="0GLPIXO"
STRING_CAPIT="0GLPIXx"
STRING_MINUS="0GLPIxx"
STRING_MAYUS="0GLPIXX"

# search and replace in all files in the plugin folder
grep -rl $STRING_PASCAL $PARENT_FOLDER_PATH | xargs sed -i "s/$STRING_PASCAL/$PLUGINNAME_PASCAL/g"
grep -rl $STRING_CAPIT $PARENT_FOLDER_PATH | xargs sed -i "s/$STRING_CAPIT/$PLUGINNAME_CAPIT/g"
grep -rl $STRING_MINUS $PARENT_FOLDER_PATH | xargs sed -i "s/$STRING_MINUS/$PLUGINNAME_MINUS/g"
grep -rl $STRING_MAYUS $PARENT_FOLDER_PATH | xargs sed -i "s/$STRING_MAYUS/$PLUGINNAME_MAYUS/g"

# rename setup_template.php to setup.php
if [ -f $PARENT_FOLDER_PATH/setup_template.php ]; then
    mv $PARENT_FOLDER_PATH/setup_template.php $PARENT_FOLDER_PATH/setup.php
fi

# execute perl modify_headers.pl to change headers
if [ -f $SCRIPT_DIR/modify_headers.pl ]; then
    perl $SCRIPT_DIR/modify_headers.pl
fi