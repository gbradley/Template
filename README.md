Template
========

PHP-based templating with mutliple inheritance.

If you don't want to learn another syntax for templating, and prefer a lightweight class over more complex solutions, then read on.

### Set up

Just require the class, then create an instance passing the path to the top-level directory containing your templates.

    $template = new Template('templates/)';
    
By default Template expects your template files to have the extension `.tpl.php`, but you can override this using the static `$extension` property.

### Creating a template file

Template files consist of 'blocks' in order to separate content. The first block in your template is the initialisation block, which you create by passing an anonymous function to the `init` method.

    <? $this->init(function(){ ?>
    Hello, world!
    <? }); ?>
    
Save this inside your template folder (in this example we'll call it `base.tpl.php`). To render the template, just pass the name of the file (without the extension) to the `render` method:

    print $template->render('base');
    
### Adding blocks

You can define more blocks with the `define` method. This method accepts a label for the block and a closure, just like `init`.

    <? $this->define('greeting', function(){ ?>
    Hello, world!
    <? }); ?>

The first argument to every block function is the template instance. This lets you embed blocks inside other blocks, by using `insert` and passing the label of the block you want to insert:

    <? $this->init(function($template){ ?>
    <? $template->insert('greeting'); ?>
    <? }); ?>
    
    <? $this->define('greeting', function(){ ?>
    <p>Hello, world!</p>
    <? }); ?>
    
### Passing arguments

Passing arguments to blocks is easy; just add them to the render method after the template name:

    $name = 'Graham';
    $template->render('base', $name);
     
You can add as many arguments as you like, and they will be passed to every block function in sequence:

    <? $this->define('greeting', function($template, $name){ ?>
    <p>Hello, <?=$name?>!</p>
    <? }); ?>
    
If you're passing more than a couple of arguments, its probably better to just pass an associative array:

    $vars = array(
        'name' 	=> 'Graham',
        'age'	=>	32
        );
    $template->render('base', $vars);
    
### Extending a template

Once you've defined a base template, you often want to be able to re-use parts of that template in other templates. By extending a template you can reuse the blocks you want, and define or override others.

To extend a template, create a new file (let's call it 'signin.tpl.php') and use the `extend` method to denote which template you're extending.

    <? $this->extend('base'); ?>
    
Because you're extending a template with an `init` method, you can skip that step and go straight to defining your blocks. You can easily overwrite an existing block:

    <? $this->define('greeting', function($template, $vars){ ?>
    <p>Hello, <?=$vars['name']?>, sign in now!</p>
    <? }); ?>
    
Alternatively you can avoid duplicating the block's content with `super`, which inserts the extended template's block:

    <? $this->define('greeting', function($template, $vars){ ?>
    <? $template->super(); ?>
    <p>Why don't you sign in now.</p>
    <? }); ?>
    
    
### Multiple inheritance

Once you've created a set of templates, you may find that you want a new template which borrows some blocks from one template, and some blocks from another.

To do this, just pass the names of both templates to `extend`:

    <? $this->extend('templateA', 'templateB'); ?>
    
The order of the arguments denotes the precedence if both templates define blocks with the same label.

