# How Extra Position 1 is added
Because OpenCart has hardcoded only 4 positions, you may experience the need to create your own positions. Luckily in the latest versions of OpenCart 2 and above it is relatively simple.

Based on our youtube video we have created this simple OpenCart extension that you can install via Extension Installer and use One more Extra Position.

## Here is a breakdown of the steps:

1. Add html code for new position to admin view;
2. Add controller for new position in catalog;
3. Add template for new position in catalog;
4. Add php code to other controllers to load new position controller;
5. Add the placeholder for the new position in the html view of your pages;

## 1. Add html code for new position to admin view
In admin/view/template/design/layout_form.twig add new block of html code for the extra position. You can simply copy one from column-right and modify the code replacing column-right with position_1.

## 2. Add controller for new position in catalog
In catalog/controller/common/ create a new file called position_1.php and copy the code from column_right.php. In the code replace ColumnRight with Position1 and column_right with position_1

## 3. Add template for new position in catalog
Create a template file catalog/view/theme/default/template/common/position_1.twig as a copy of column_right.twig. Edit the Html code to fit your needs (for example replace the id with position_1 and col-sm-3 with col-sm-12)

## 4. Add php code to other controllers to load new position controller
In all pages load the controller for the new position (for example in catalog/controller/common/home.php load the new controller after column_right)

## 5. Add the placeholder for the new position in the html view of your pages
And in catalog/view/theme/default/template/common/home.twig add the placeholder php code display the new position.
