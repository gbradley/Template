<?php

/**
* Native PHP templating with multiple inheritance
**/

class Template {

	protected $tree 			= null;				// template inheritance tree
	protected $options  		= null;
	private $current_tpl 		= null;				// the current template
	private $current_label 		= null;				// the current block label
	private $args 				= null;				// arguments to forward to blocks

	public static $extension 	= '.tpl.php';		// the template extension
	protected static $closures 	= null;				// stores closures for blocks


	/**
	* @param string $path path to template directory
	**/
	public function __construct($path) {
		$this->path = $path;
	}

	/**
	* Adds templates to the inheritance tree.
	* 
	* @param string $tpl template name
	* @param string $multi_tpl... define more templates for multiple inheritance
	**/
	public function extend() {
		$this->tree[] = func_get_args();
	}
	
	/**
	* Generate and return the template output.
	* 
	* @param string $tpl (optional) the template name to render
	* @param mixed $args (optional) Any arguments get passed into all insert functions.
	* @return string HTML output
	**/
	public function render() {

		self::$closures = array();
	
		$args = func_get_args();										// grab all arguments 
		$this->tree = array(array(array_shift($args)));
		$this->args = $args;

		$i = 0;
		ob_start();
		while ($i < count($this->tree)) {								// work up through the template tree
			$branch = $this->tree[$i];
			$j = 0;
			while ($j < count($branch)){								// work through the branch
				$this->current_tpl = $branch[$j];
				require_once($this->path . self::pathForTemplate($this->current_tpl));
				$j++;
			}
			$i++;
		}
		ob_clean();
		$this->runBlock();
		$contents = ob_get_contents();
		ob_end_clean();

		self::$closures = null;
		return trim($contents);
	}

	/**
	* Insert a block.
	* 
	* @param string $label The label of the block to insert
	* @param mixed $args (optional) Any extra arguments get added to the arguments array from render() and passed into the block.
	**/
	public function insert() {

		$args = func_get_args();
		$label = array_shift($args);

		$tmp = $this->current_label;								// store the name of the current insert, then override with the new one
		$this->current_label = $label;

		foreach ($this->tree as $branch) {							// loop through the tree until we find a match
			foreach ($branch as $tpl) {
				if ($this->runBlock($label, $tpl, $args)) {
					break 2;
				}
			}
		}
		$this->current_label = $tmp;								// revert back to old insert name
	}
	
	/**
	* When called from inside a block, inserts the corresponding block from the closest ancestor or sibling template.
	**/
	public function super() {
		foreach ($this->tree as $i => $branch) {						// loop through tree and find the position of the current template
			$index = array_search($this->current_tpl, $branch);
			if ($index !== false && $i < count($this->tree) - 1) {		// check the template isn't at the top of the tree
				for ($j = $i + 1; $j < count($this->tree); $j++) {		// work upwards through the remaining groups
					$group = $this->tree[$j];
					foreach ($group as $tpl) {							// work sideways through each group
						if ($this->runBlock($this->current_label, $tpl)) {
							break 3;
						}
					}
				}
			}
		}
	}

	/**
	* Convert characters to their HTML entity equivalent.
	*
	* @param string $value
	* @param string
	**/
	public function escape($value) {
		return htmlentities($value, ENT_COMPAT, 'UTF-8');
	}

	/**
	* Register the initiialise block.
	*
	* @param function $closure The block closure
	**/
	public function init($closure) {
		self::$closures['*'] = $closure;
	}

	/**
	* Register a block.
	*
	* @param string $label The block label
	* @param function $closure The block closure
	**/
	public function define($label, $closure) {
		self::$closures[$this->current_tpl .'_' . $label] = $closure;
	}

	/**
	* Finds and runs a block, passing through any optional arguments
	*
	* @param string $label (optional) The block label, or null for the initialise block
	* @param string $tpl (optional) The name of a template
	* @param array $pass_thru (optional) Array of arguments to pass sequentially to the block
	* @return boolean
	**/
	private function runBlock($label = null, $tpl = null, $pass_thru = null) {
		$block = self::getBlock($label ? ($tpl . '_' . $label) : '*');
		if ($block) {
			$args = $this->args;
			array_unshift($args, $this);
			if ($pass_thru) {
				$args = array_merge($args, $pass_thru);
			}
			call_user_func_array($block, $args);
			return true;
		} else {
			return false;
		}
	}

	/**
	* Return the path for a template name
	*
	* @param string $tpl
	* @return string
	**/
	public static function pathForTemplate($tpl) {
		return $tpl . self::$extension;
	}

	/**
	* Return the template name for a path
	*
	* @param string $path
	* @return string
	**/
	public static function templateForPath($path) {
		return str_replace(self::$extension, '', $path);
	}

	/**
	* Aceepts a label and returns the block closure if found.
	*
	* @param string $blockName
	* @return function
	**/
	private static function getBlock($blockName) {
		return isset(self::$closures[$blockName]) ? self::$closures[$blockName] : null;
	}

}

?>
