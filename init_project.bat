@echo off & SETLOCAL ENABLEEXTENSIONS ENABLEDELAYEDEXPANSION
echo                                      ---WARNING---
echo.
echo [7;34m              Moodle DEPLOY PROJECT!             [0m

git init
git remote add origin https://gitlab.com/SmartAppTech/sqlschool.git
git fetch
git pull origin master

cd auth/stripe

echo.
echo [7;34m              Composer NPM!             [0m
composer install





