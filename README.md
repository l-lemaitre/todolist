ToDoList
========

Project 8 of the formation [Application Developer - PHP/Symfony of OpenClassrooms](https://openclassrooms.com/fr/paths/500-developpeur-dapplication-php-symfony).

## Project badges ##
[![Maintainability](https://api.codeclimate.com/v1/badges/b9a783f630e43275a542/maintainability)](https://codeclimate.com/github/l-lemaitre/todolist/maintainability)

[![Test Coverage](https://api.codeclimate.com/v1/badges/b9a783f630e43275a542/test_coverage)](https://codeclimate.com/github/l-lemaitre/todolist/test_coverage)

## Prerequisites ##
- PHP 8.2

## Instructions for installing the project ##
- Pull the project with the command git clone and this repository URL (https://github.com/l-lemaitre/todolist) to the root of your working directory

- Install the dependencies

  Run the command :
  ```
  composer install
  ```
- Install the mysql "todolist" database on your web server

  Run the commands :
  ```
  php bin/console doctrine:database:create
  php bin/console doctrine:migrations:migrate
  ```
- File to modify to establish a connection with the "todolist" database

  .env or .env.local

## Appendices ##
- To contribute to the project, see this [document](/CONTRIBUTING.md).
- The technical documentation is accessible at this [URL](/appendices/technical_documentation.pdf).
