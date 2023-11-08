# Cypress Integration Tests

[Cypress](https://www.cypress.io/) is an end-to-end testing framework written in javascript.

An overview and documentation for Cypress are [available here](https://docs.cypress.io/guides/overview/why-cypress.html#In-a-nutshell).

The documentation on using Cypress with ExpressionEngine is [available here](https://docs.expressionengine.com/latest/development/cypress-tests.html).

## Install Cypress

`cd tests/cypress && npm install`

## Configure Environment

You can change the environment constants to match your local setup by copying the example file as shown below and modifying its values.  This new file will be excluded from version control.

`cp tests/cypress/cypress.env.example.json tests/cypress/cypress.env.json`

## Set up Composer
`cd tests/cypress`  and then run `composer install`

## Start PHP Server

Copy the configuration file into place and then start the standalone PHP server

`cp tests/cypress/support/config/config.php system/user/config/config.php && php -S localhost:8888`

Open Cypress to run tests (GUI)

`cd tests/cypress && npm run cypress:open`

Run Cypress tests (CLI)

`cd tests/cypress && npm run cypress:run`

## Get list of existing tests

`npx print-tests cypress/integration/**/*.ee6.js > tests.txt`
