# powerorm 
A light weight orm inspired by Django ORM for Codeigniter.

# Introduction
I created this project because i required a lightweight easy to use orm that i could use in my Codeigniter projects 
with the least amount of configuration . sort of `plug and play` if you will. 
while at the same time reducing repetition and providing a consistent way to deal with databases.

That is, i wanted to avoid the repetitive actions of creating migration files, creating query method to query the 
database and also wanted to be able to see all my database table fields on my models without me going to the database 
tables themselves and use this fields to interact with the database.


# Dependecies
This orm heavily relies on the core libraries provided with CodeIgniter without making any alterations on them.

- The CodeIgniter Migration library

- The CodeIgniter Database classes

# Configuration
 - Enable migrations 
 Locate `application/config/migration.php` and enable migration. ```$config['migration_enabled'] = TRUE;```
 - Load the **powerorm** library. preferable on autoload. ```$autoload['libraries'] = array('powerorm/orm',);```

# Features
 - Create automatic migration files
 - Provides database interaction methods
 
# How It works

The orm takes each model created to represent a database table, it also takes any fields defined in the **fields()**
 method to represent a column of that table. 
 
# Usage 

## 1. The models
To Start using the orm, create models as you normally would in CodeIgniter and extend the **PModel** instead of 
**CI_Model** as shown below .
    
    class User extends PModel{
        
        public function fields(){
            $this->username = new CharField(['max_length'=>25]);
            $this->f_name = new CharField(['max_length'=>25]);
            $this->l_name = new CharField(['max_length'=>25, 'form_widget'=>'textarea']);
            $this->password = new CharField(['max_length'=>65]);
            $this->age = new CharField(['max_length'=>65, 'form_widget'=>'currency', 'empty_label'=>'%%%%']);
            $this->email = new EmailField();
            $this->roles = new ManyToMany(['model'=>'role']); // Many To Many Relationship roles model
        }
    }
    
    class Role extends PModel{
    
        public function fields(){
            $this->name = new CharField(['max_length'=>25]);
            $this->created_on = new DateTimeField(['on_creation'=>TRUE]);
            $this->updated_on = new DateTimeField(['on_update'=>TRUE]);
        }
    }
    
    class Profile extends PModel{
    
        public function fields(){
            $this->user = new OneToOneField(['model'=>'user', 'primary_key'=>TRUE]); // One To One Relationship to user model
            $this->town = new CharField(['max_length'=>30, 'db_index'=>TRUE]);
            $this->country = new CharField(['max_length'=>30, 'unique'=>TRUE,'null'=>FALSE, 'default'=>'kenya']);
            $this->box = new CharField(['max_length'=>30]);
            $this->code = new CharField(['max_length'=>30]); 
            $this->ceo = new ForeignKey(['model'=>'user']);  // One To Many Relationship to user model
        }
    
    } 
    
The above 3 methods extend **PModel** , Extending this model requires that you implement the **fields()** method.
The Main purpose of this method is to create fields that the model will use to map to database table columns.

The orm provides different field type that represent the different types of database columns 
e.g. **CharField** represent **varchar** type, **http://f.com** for more.




## 2. Migration
Once you have the models created, on the command line/ terminal, run the following command

`php index.php migrations/makemigrations`

Once the files have been generated you can run the following command to actually create the tables in the database.

`php index.php migrations/migrate`

This will generate all the migration required to create the above models table in the database

Looking at the roles model, it will generate a migration file that looks as shown below:

    // Migration for the model role
    
    use powerorm\migrations\RunSql;
    
    class Migration_Create_Role_1456906961_1171 extends CI_Migration{
    
        public $model= 'role';
        public $depends= [];
    
        public function up(){
            RunSql::add_field("name VARCHAR(25)  NULL");
            RunSql::add_field("id INT UNSIGNED NOT NULL AUTO_INCREMENT");
            RunSql::add_field_constraint("PRIMARY KEY (id)");
            RunSql::create_table("ormtest_role", TRUE, ['ENGINE'=>'InnoDB']);
        }
    
        public function down(){
            RunSql::drop_table("ormtest_role", TRUE);
        }
    
        public function state(){
            return	[
                'model_name'=>'role',
                'operation'=>'add_model',
                'fields'=>	[
                    'name'=>	[
                        'field_options'=>	[
                            'name'=>'name',
                            'type'=>'VARCHAR(25)',
                            'null'=> TRUE,
                            'unique'=> FALSE,
                            'max_length'=>25,
                            'primary_key'=> FALSE,
                            'auto'=> FALSE,
                            'default'=> NULL,
                            'signed'=> NULL,
                            'constraint_name'=>'',
                            'db_column'=>'name',
                            'db_index'=> NULL,
                        ],
                        'class'=>'CharField',
                    ],
                    'id'=>	[
                        'field_options'=>	[
                            'name'=>'id',
                            'type'=>'INT',
                            'null'=> FALSE,
                            'unique'=> FALSE,
                            'max_length'=> NULL,
                            'primary_key'=> TRUE,
                            'auto'=> TRUE,
                            'default'=> NULL,
                            'signed'=>'UNSIGNED',
                            'constraint_name'=>'',
                            'db_column'=>'id',
                            'db_index'=> NULL,
                        ],
                        'class'=>'AutoField',
                    ],
                ],
            ];
        }
    
    }

## 3. Querying
The PModel class also provides several methods that can be used to interact with the database tables represented by 
each model.

e.g. inside the controller

     public function index(){
        $this->load->model('user');
        
        $this->user->all(); // fetch all users in the database tabble represented by model user
        
        $this->user->get(1); // get user with the primary key 1
        
        $this->user->get(['username'=>'sia']); // get user with the username sia
        
        $this->user->filter(['l_name'=>'sia']); // get all users with the l_name sia
        
        // To save a user
        $user1 = $this->user;
        $user1->username = 'matt';
        $user1->password = '$qwer#$';
        $user1->save();
     }

## 4. Form
The PModel also provides a method that generated the form for a specific model.

e.g. inside the controller

    public function index(){
        $this->load->model('user');
        
        $form = $this->user->get(1)->as_form(); // generate form from the model returned by get()
        
        $form->only(['password','username']);  // for the form only use password and username
        
        $form->create(); // create the actual form
        
        $data['form'] = $form;
        
        // load it on view
        $this->load->view('user_view', $data);
    }

# Requirements on 
 - CodeIgniter 3.0+
 - php 5.4+
 - on MYsql 5.6.5
 