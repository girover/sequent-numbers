# Sequent Numbers Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/girover/sequent-numbers.svg?style=flat-square)](https://packagist.org/packages/girover/sequent-numbers)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/girover/sequent-numbers/run-tests?label=tests)](https://github.com/girover/sequent-numbers/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/girover/sequent-numbers/Check%20&%20fix%20styling?label=code%20style)](https://github.com/girover/sequent-numbers/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/girover/sequent-numbers.svg?style=flat-square)](https://packagist.org/packages/girover/sequent-numbers)

---
## Content

  - [Introduction](#introduction)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Configuration](#configuration)
  - [Usage](#usage)
    - [Making Numbers](#making-numbers)
    - [Storing Numbers In Database](#storing-numbers-in-database)
  - [Testing](#testing)
  - [Changelog](#changelog)
  - [Contributing](#contributing)
  - [Security Vulnerabilities](#security-vulnerabilities)
  - [Credits](#credits)
  - [License](#license)


## Introduction
**girover/sequent-numbers** is a package for generating sequence of numbers.   
And possibility of storing them in database.
## Prerequisites
- Laravel 8+
- PHP 8+
- Mysql 5.7+
## Installation
You can add the package via **composer**:

```bash
composer require girover/sequent-numbers

```
Before installing the package you should configure your database.   

## Usage

 ### getting set of numbers
To make a set of numbers in memory, you can do this.

 ```php
    // InAController
    use \Girover\SequentNumbers\Numbers;

    $numbers = new Numbers;

    $numbers->from('00000')->to('99999')->get()
    // This will return Illuminate\Database\Eloquent\Collection

 ```

 To add some constraints on your numbers:

 ```php
    // InAController
    use \Girover\SequentNumbers\Numbers;

    $numbers = new Numbers;
    $numbers->from("0000")->to("9999");
    $numbers->query()->where('number', '>', '1000')->get();
    // This will return Illuminate\Database\Eloquent\Collection
    
    $numbers->query()->whereBetween('number', ["5555","7777"])->get();
    // This will return Illuminate\Database\Eloquent\Collection

 ```

 ### Storing Numbers in Database

 To store your created numbers in the database, you can do this:

 
 ```php
    // InAController
    use \Girover\SequentNumbers\Numbers;

    $numbers = new Numbers;
    $numbers->from("0000")->to("9999");

    $numbers->storeInTable('my_numbers_table');

 ```
 **Note**: If the name of table already exists in database, the table should has 
 a column called 'number' with varchar(255) type.