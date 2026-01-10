# Sentinel

## Installation

Install PHP 8.4+ and MySQL 8.0+ in your system.

Checkout the project:

```shell
git clone git@github.com:ninsuo/sentinel.git
```

Download the symfony-cli tool if you don't have it already:

```shell
wget https://get.symfony.com/cli/installer -O - | bash
```

Install dependencies:

```shell
cd sentinel
composer install
```

Install certificates:

```shell
symfony server:ca:install 
```

## Launch

```shell
symfony local:server:start 
```
