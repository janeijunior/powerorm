# powerorm 
A light weight easy to use CodeIgniter ORM.

# Introduction
I created this project because i required a lightweight easy to use orm that i could use in my Codeigniter projects 
with the least amount of configuration . sort of `plug and play` if you will. 
while at the same time reducing repetition and providing a consistent way to deal with databases.

That is, i wanted to avoid the repetitive actions of creating migration files, creating query method to query the 
database and also wanted to be able to see all my database table fields on my models without me going to the database 
tables themselves and use this fields to interact with the database.

This ORM is heavily inspired by Django ORM. Because i personally love how there orm works.

# Install

- Via Composer

`composer require eddmash/powerorm`

- Download or Clone package from github.

# Load the Library

Load the library like any other Codeigniter library.

`$autoload['libraries'] = array('session', 'powerorm/orm', 'powerauth/auth')`


# How It works

 - [Powerorm v1.1.0-pre-alpha](readme/1_1_0.md)

 - [Powerorm v1.0.1](readme/1_0_1.md)


# Features
 - Allows to fully think of the database and its table in an object oriented manner i.e. 
    table are represented by model and columns are represented by fields.
 - Create automatic migrations.
 - Create forms automatically based on models.
 - All fields visible on the model, no need to look at the database table when you want to interact with the database.
 - Provides database interaction methods
 