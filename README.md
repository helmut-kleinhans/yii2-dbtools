##Check and sync database structure with filesystem and subversion

Database Backup and Restore functionality

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist helmut-kleinhans/yii2-dbtools "*"
```

or add

```
"helmut-kleinhans/yii2-dbtools": "*"
```

to the require section of your `composer.json` file.


## Usage

Once the extension is installed, simply add it in your config by  :

Basic ```config/web.php```

Advanced ```[backend|frontend|common]/config/main.php```

        'modules'    => [
            'dbtools' => [
                'class' => 'DbTools\DbToolsModule',
                //'checkDefiner' => 'root@%',       //used for manage tool to show warning if definer of procedure, 
                                                    //function or trigger definer doesnt match
                //'exportDelimiter' => '$$',        //delimiter which will be used for export of Procedures,Functions 
                                                    //and Triggers
                //'xmlValues' => '@app/values.xml', //input file for constants and error values
                /*
                //define behaviors for the manage controller
                'behaviorsManage' =>
                    [
                    'access' => [
                        'class' => \yii\filters\AccessControl::className(),
                        'rules' => [
                            [
                                'allow' => true,
                                'matchCallback' => function ($rule, $action) {
                                    return \backend\models\User::checkRoutePermission($action->id,$action->controller->id,$action->controller->module->id);
                                }
                            ],
                        ],
                    ],
                ],
                */
            ],
            ...
        ],
        'aliases'    => [
            '@DbToolsExport' => '@app/dbtools',     //folder to save exported and autogenerated files
            ...
        ],

## Usage


        
## Manage
Pretty Url's ```/dbtools/manage```

No pretty Url's ```index.php?r=dbtools/manage```

The DbTool interface reads out your database and does some checks.

###tables
Can be saved to filesystem but need to be restored by hand.
- Warnings
    - You can add a Warning by setting a table column comment to "Warning: xxxxx"

###procedures + functions + triggers
Can be saved to filesystem and also be restored.
- Brief Section (see **Brief**)                    
- Warnings
    - Check-Warnings:
        - if SECURITY TYPE is not INVOKER
        - if DEFINER is not the definer set in config (modules=>dbtools=>checkDefiner)
        - if a DECLARE doesnt start with "m_" (DECLARE m_MyVar INT DEFAULT 0 ;)
        - if a DECLARE known ERROR doesnt match the error value / error type
        - if a DECLARE known CONST doesnt match the error value / error type
        - if a DECLARE starts with cConst or cError but is not set in values.xml
    
###views
Can be saved to filesystem but need to be restored by hand.

###events
Can be saved to filesystem but need to be restored by hand.

#### Brief
The brief section should be at the top of your procedure/function/trigger

    CREATE DEFINER=`root`@`%` PROCEDURE `myproc`(
                   IN  inVar  INT UNSIGNED,
                   OUT outVar BIGINT,
               )
           BEGIN
               /**
               @brief description of myproc
               @param  inVar      description of param1
               @param  outVar     description of param2
               */
               
               DECLARE  ...


- @brief 
    - description of this item        
- @param [ParameterName]
    - description for the given parameter
- @return
    - description of function return value
- @warning [Warning Message Text]
    - adds a warning to manage interface
- @note [Note Text]
    - adds a note to manage interface
- @deprecated
    - used to mark the item as deprecated and adds a warning
- @todo [Todo Text]
    - adds a todo to manage interface 
- @export
    - marks the item for autogeneration (see **Autogen**)
- @select Name
    - You must add a "@select Name" for each select your procedure or subprocedures are outputting. Name is used for 
    autogenerated classes to receive results in php (see **Autogen**) (Note: @export will be set to true)

## Autogen
Pretty Url's ```/dbtools/manage/autogen```

No pretty Url's ```index.php?r=dbtools/manage/autogen```

The auto generation tool is used to generate PHP files out of input xml (see **values.xml**) and Db procedures/functions (see **Manage**).

### Autogenerated values

You can find an example.xml inside the extension folder

    values_example.xml

Please copy the file to your web folder and set the path inside your config (modules->dbtools->xmlValues)

This file is used to generate a php file, which contains user defined constants, errorcodes and also values that get 
exportet out of db.

The location where the generated file is saved can be set in config (aliases->@DbToolsExport). 
The final path will be:

    @DbToolsExport/dbvalues/DbValues.php

Example

    <cat name="CatName1">
        <cat name="CatName2">
            <enum value="100">
                <error name="error1">Error Message1</error>
                <error name="error2">Error Message2</error>
            </enum>
        </cat>
    </cat>
    <cat name="CatName3">
        <const name="const1" type="INT" value="1"/>
        <const name="const2" type="UNSIGNED INT" value="2"/>
        
        <sql name="dbvalues" db="db" table="tablename" colname="col4name" colvalue="col4value"/>

        <sqlenum name="dbenums" db="db" table="tablename" colenum="col4enum"/>
    </cat>

Will result in:
    
    <?php
    namespace DbToolsExport\dbvalues;
    
    class DbValues
    {
        //CatName1_CatName2
        const eError_CatName1_CatName2_error1 = 100;
        const eError_CatName1_CatName2_error2 = 101;
        //CatName3_dbvalues
        const cConst_CatName3_dbvalues_value1 = 1;                                     //value 1
        const cConst_CatName3_dbvalues_value2 = 2;                                     //value 2
        //CatName3_dbenums
        const cConst_CatName3_dbenums_enum1 = 'enum_1';
        const cConst_CatName3_dbenums_enum2 = 'enum_2';
        const cConst_CatName3_dbenums_enum3 = 'enum_3';
        //CatName3
        const cConst_CatName3_const1 = 1;
        const cConst_CatName3_const2 = 2;
    
        const ErrorMessages = [
                //CatName1_CatName2
                self::eError_CatName1_CatName2_error1 => 'Error Message1',
                self::eError_CatName1_CatName2_error2 => 'Error Message2',
                //CatName3_dbvalues
                //CatName3_dbenums
                //CatName3
        ];
    
        const DbTypes = [
                'cConst_CatName3_dbvalues_value1' => 'TINYINT UNSIGNED',
                'cConst_CatName3_dbvalues_value2' => 'TINYINT UNSIGNED',
                'cConst_CatName3_dbenums_enum1' => 'CHAR(7)',
                'cConst_CatName3_dbenums_enum2' => 'CHAR(7)',
                'cConst_CatName3_dbenums_enum3' => 'CHAR(7)',
                'cConst_CatName3_const1' => 'INT',
                'cConst_CatName3_const2' => 'UNSIGNED INT',
        ];
    
        const Keys = [
            'error'=>[
                'eError_CatName1_CatName2_error1',
                'eError_CatName1_CatName2_error2',
            ],
            'const'=>[
                'cConst_CatName3_dbvalues_value1',
                'cConst_CatName3_dbvalues_value2',
                'cConst_CatName3_dbenums_enum1',
                'cConst_CatName3_dbenums_enum2',
                'cConst_CatName3_dbenums_enum3',
                'cConst_CatName3_const1',
                'cConst_CatName3_const2',
            ],];
    
        public static function MessageByCode($errorcode)
        {
            if (!array_key_exists($errorcode, static::ErrorMessages))
            {
                return 'Unknown errorcode: ' . $errorcode;
            }
    
            return static::ErrorMessages[$errorcode];
        }
        
    }
    /*
    DECLARE eError_CatName1_CatName2_error1 SMALLINT UNSIGNED DEFAULT 100;
    DECLARE eError_CatName1_CatName2_error2 SMALLINT UNSIGNED DEFAULT 101;
    DECLARE cConst_CatName3_dbvalues_value1 TINYINT UNSIGNED DEFAULT 2;
    DECLARE cConst_CatName3_dbvalues_value2 TINYINT UNSIGNED DEFAULT 1;
    DECLARE cConst_CatName3_dbenums_enum1 CHAR(7) DEFAULT 'enum_1';
    DECLARE cConst_CatName3_dbenums_enum2 CHAR(7) DEFAULT 'enum_2';
    DECLARE cConst_CatName3_dbenums_enum3 CHAR(7) DEFAULT 'enum_3';
    DECLARE cConst_CatName3_const1 INT DEFAULT 1;
    DECLARE cConst_CatName3_const2 UNSIGNED INT DEFAULT 2;
    
    */

#### Tags

##### Category
Is used for grouping elements.

    <cat name="CatName1" type="INT UNSIGNED">
    
- name    
    - The name is put in front of the children's name.
- type    
    - [Optional] The type is used to create the sql declare type. You can specify the type here to pass it on to all 
    children, or place it separately in each child.

##### Enum
Is used to automatically set values to child elements and also increment them after each child.

    <enum value="100">
    
- value
    - The value is increased by 1 after each child.  
- type    
    - [Optional] The type is used to create the sql declare type. You can specify the type here to pass it on to all 
    children, or place it separately in each child.
    
##### Error
Error codes should be used as Exceptions inside db procedures, functions and triggers and can be reveived via 
autogenerated classes. (see **Manage** and **Autogen**) 

    <error name="error1">Error Message1</error>
    
- name    
    - The name is used for that error
- value
    - need to have ether value or get value from <enum>
- Text    
    - Text is used as default error message for db exception. You can set a custom message when you throw the error 
    in db.

##### Constant
Should be used ether in php or in db. (see **Manage**)

    <const name="const2" type="UNSIGNED INT" value="2"/>
    
- name    
    - The name is used for that constant
- value
    - need to have ether value or get value from <enum> or set "bit"
- bit
    - Used for generating bit values. You can give the Bits that should be set starting with 1. 
    - Example to set bits 0x0001b, 0x0100b, 0x1000b =>  bit="1,3,4" => 13dec
- type    
    - [Optional] The type is used to create the sql declare type. You can specify the type here or get it from <cat>

##### Sql
Used to convert content of a db table to useable PHP constants.

    <sql name="dbvalues" db="db" table="tablename" colname="col4name" colvalue="col4value"/>

- name    
    - The name is put in front of the children's name.
- db
    - yiis db connection identifier used in yii config.xml
-table
    - table that should be converted
-colname
    - column that will be used for the name of the variables ( Note: all characters get removed that don't fit 
    "a-zA-Z0-9_" and starting letter and letters after '_' will be set to uppercase )
-colvalue
    - column that will be used for the value of the variables
    
##### Sql Enum
Used to convert db column enums to useable PHP constants.

    <sqlenum name="dbenums" db="db" table="tablename" colenum="col4enum"/>        
        
- name    
    - The name is put in front of the children's name.
-table
    - table that should be converted
-colenum
    - column that will be used for the name and value of the variables ( Note for name: all characters get removed 
    that don't fit "a-zA-Z0-9_" and starting letter and letters after '_' will be set to uppercase )

#### Usage
To use autogenerated files you need to include

    use DbToolsExport\dbvalues\DbValues;
    
and access the values by:

    $error = DbValues::eError_CatName1_CatName2_error1;
    
### Autogenerated classes

This file is used to generate a php file, which contains db Procedure or Function calls including the thrown error Exceptions. 
Furthermore it also adds class members for OUT parameters which will be filled automatically. 
Procedures can have select resultsets  /see **Brief/select**.
Functions have a return value function

To mark a procedure or function for export see **Brief/export**.

The location where the generated files are saved can be set in config (aliases->@DbToolsExport). 
The final path will be:

    @DbToolsExport/dbclasses/{db}/dbProc{procedurename}.php
    @DbToolsExport/dbclasses/{db}/dbFunc{functionname}.php
    
- db
    - db connection key defined in config
- procedurename
    - name of the procedure
- functionname
    - name of the function

#### Usage
To use autogenerated classes you need to include

    use DbToolsExport\dbclasses\db\dbFuncMyFunc;
    //optional to receive exceptions
    use DbTools\db\DbException;                                     
    
and execute it by:

    $q = new dbFuncMyFunc('v1');
    try
    {
        $q->execute();
    }
    catch (DbException $e)
    {
        echo 'ERROR ('.$e->getCode().'): '.$e->getMessage();
    }
    
    echo $q->getResult();
    