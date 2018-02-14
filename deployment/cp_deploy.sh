#!/bin/bash

#Variable names
PROJECT_NAME=naturskolan_database
PROJECT_PATH=~/$PROJECT_NAME
STAGING_PATH=/cygdrive/d/staging/$PROJECT_NAME
GITHUB_PROJECT=https://github.com/fridde/naturskolan_database.git


cd $STAGING_PATH/../
chmod -R 0777 PROJECT_NAME
mv $PROJECT_NAME ${PROJECT_NAME}_old

git clone --depth 1 $GITHUB_PROJECT
cd $PROJECT_NAME
composer update --no-dev

cp $PROJECT_PATH/deployment/deployment.ini $STAGING_PATH/deployment/deployment.ini
cp $PROJECT_PATH/config/settings_prod.yml $STAGING_PATH/config/settings.yml

