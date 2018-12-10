# How to recorever admin password

VIDEO https://www.youtube.com/watch?v=BC6LJIeHaHo

Hi, if you have logged out of OpenCart Admin panel and forgot your password, here are some simple tips how to recover one.

Of course, the easist way is to use your Email. But if you used a fake email when creating your OpenCart Admin user, this option does not work for you.

## Solution 1 - Auth file

1. auth1d_DELETE_AFTER_USE.php - will live for one day and then will be deleted when opened again.
2. auth.php - will be deleted after first use.

Use our authantication file. Just drop it into your OpenCart Admin folder and go to 

### Installation
1. Via file manager go to opencart root folder / admin. 
2. Upload one of these two files (which ever works for you)
3. Open this file via browser (for example: http://yoursite.com/admin/auth1d_DELETE_AFTER_USE.php or http://yoursite.com/admin/auth.php)

## Solition 2 - PhpMyAdmin

1. Via Cpanel go to your phpmyadmin 
2. Find your OpenCart database
3. Locate table oc_user
4. Edit User with id 1
  - password: 5a80088bd1e4fa5a25b66bbe6867fc4cce3b1539
  - salt: 4zsCfjJvm
5. Go to your OpenCart admin and enter
  - user: admin
  - password: 1234
  
Just remeber to change your password to something more secure. 


## Solution 3 - Edit code

1. Via Cpanel go to Filemanager (or via FTP)
2. Go to OpenCart admin/controller/common/login.php
3. Edit line 14

```php
if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
```

to 

```php
$this->session->data['user_id'] = 1;
if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
```

4. Go to OpenCart Admin and login
5. Revert back the code changes (otherwise anyone can login without a password)
