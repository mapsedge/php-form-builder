<?php

// v 0.9.0

// Bill R Morris notes, 2020-07
// The class as originally written was for one-direction, front-end forms,
// drawn and presented by the user for submitting information.
// This iteration makes four major changes:
// 1: the class can pull form information from tables (defined below)
// 2: the form can how populate with data from the back end,
//    so it can be used bi-directionally
// 3: grouping is added. Controls are grouped inside fieldsets
//    using vanilla javascript after the form is built.
// 4: a control type: flags is added. The class puts a div on the screen,
//    then vanilla javascript converts it into checkboxes that feed
//    a hidden control.
// Other changes as noted inline.
// NOTE: there are some methods used that are particular to our system
// 		related to database connectivity. You will undoubtedly need to change those.
// MSSQL Server table creation SQL is in form-tables.sql

class PhpFormBuilder { 

	private $db = null;

	// Stores all form inputs
	private $inputs = [];

	// Stores all form attributes
	private $form = [];

	// Does this form have a submit value?
	private $has_submit = false;

	/* BRM
	the list of groups, fields, and attribtes from the formFields table.
	Instead of building a form manually, pass in $idform and the 
	form elements will be pulled and built automatically.
	This is meant to compliment, not replace, manual field entry
	*/ 
	public $idform = 0;

	/* BRM 
	$data is an associative array.
	To populate the form values if exists
	the formFields table has a "mapsTo" field.
	If $data['somekey'] == mapsTo, then the
	field is populated with that value.
	*/
	private $data = [];

	/* BRM
	flags are rendered as a series of checkboxes, bitwise values, 
	feeding additively into one hidden field
	Example: 2: red; 4: green; 8: blue; 16: yellow; 32: orange 
	... n * 2 up to the int limit
	So, for flag value: 
		6 = red and green; 
		40 = blue and orange; 
		20 = green and yellow; 
		4 = green; 
		2 = red;
	*/
	private $flags = [];

	private $input_defaults = [
		'add_label'		=> true,
		'after_html'	=> '',
		'autofocus'		=> false,
		'before_html'	=> '',
		'checked'		=> false,
		'class'			=> array(),
		'group'			=> '',
		'id'			=> '',
		'label'			=> '',
		'max'			=> '',
		'maxlength'		=> '',
		'min'			=> '',
		'name'			=> '',
		'options'		=> array(),
		'pattern'		=> '',
		'request_populate' => true,
		'placeholder'	=> '',
		'required'		=> false,
		'selected'		=> false,
		'size'			=> '',
		'step'			=> '',
		'type'			=> 'text',
		'validateas'	=> '',  // BRM: write your own js to use this(1)
		'value'			=> '',
		'wrap_class'	=> array( 'form_field_wrap' ),
		'wrap_id'		=> '',
		'wrap_style'	=> '',
		'wrap_tag'		=> 'div'
	];
	/* 
	(1) Example: assign each field a 'validateas' value, like 'areaphone'
	or 'zipcode'. Before form submit, do a pre-flight validation: wrap up each
	control value and 'validateas' value, send them to the server for validation.
	(Of course, you have to write the javascript and the server-side 
	validation routines.)
	The HTML 5 form attribute 'pattern' makes this *mostly* unnecessary
	*/

	/**
	 * Constructor function to set form action and attributes
	 *
	 * @param string $action
	 * @param bool   $args
	 */
	function __construct( $action = '', $args = false ) {

		// Default form attributes
		$defaults = array(
			'action'	   => $action,
			'method'	   => 'post',
			'enctype'	  => 'application/x-www-form-urlencoded',
			'class'		=> array(),
			'id'		   => $this->generateRandomString(20),
			'markup'	   => 'html',
			'novalidate'   => false,
			'add_nonce'	=> false,
			'add_honeypot' => true,
			'form_element' => true,
			'add_submit'   => true
		);

		// Merge with arguments, if present
		if ( $args ) {
			$settings = array_merge( $defaults, $args );
		} // Otherwise, use the defaults wholesale
		else {
			$settings = $defaults;
		}

		// Iterate through and save each option
		foreach ( $settings as $key => $val ) {
			// Try setting with user-passed setting
			// If not, try the default with the same key name
			if ( ! $this->set_att( $key, $val ) ) {
				$this->set_att( $key, $defaults[ $key ] );
			}
		}
	}

	/**
	 * Validate and set form
	 *
	 * @param string		$key A valid key; switch statement ensures validity
	 * @param string | bool $val A valid value; validated for each key
	 *
	 * @return bool
	 */
	function set_att( $key, $val ) {

		switch ( $key ) :

			case 'action':
				break;

			case 'method':
				if ( ! in_array( $val, array( 'post', 'get' ) ) ) {
					return false;
				}
				break;

			case 'enctype':
				if ( ! in_array( $val, array( 'application/x-www-form-urlencoded', 'multipart/form-data' ) ) ) {
					return false;
				}
				break;

			case 'markup':
				if ( ! in_array( $val, array( 'html', 'xhtml' ) ) ) {
					return false;
				}
				break;

			case 'class':
			case 'id':
				if ( ! $this->_check_valid_attr( $val ) ) {
					return false;
				}
				break;

			case 'novalidate':
			case 'add_honeypot':
			case 'form_element':
			case 'add_submit':
				if ( ! is_bool( $val ) ) {
					return false;
				}
				break;

			case 'add_nonce':
				if ( ! is_string( $val ) && ! is_bool( $val ) ) {
					return false;
				}
				break;

			default:
				return false;

		endswitch;

		$this->form[ $key ] = $val;

		return true;

	}

	/**
	 * Add an input field to the form for outputting later
	 *
	 * @param string $label
	 * @param string $args
	 * @param string $slug
	 */
	function add_input( $label, $args = '', $slug = '' ) {

		if ( empty( $args ) ) {
			$args = array();
		}

		// Create a valid id or class attribute
		if ( empty( $slug ) ) {
			$slug = $this->_make_slug( $label );
		}

		$defaults = $this->input_defaults;
		$defaults['name'] 	= $slug;
		$defaults['id'] 	= $slug;
		$defaults['label'] 	= $label;

		// Combined defaults and arguments
		// Arguments override defaults
		$args				   = array_merge( $defaults, $args );
		$this->inputs[ $slug ] = $args;

	}

	//--------------------------------------------------------------------------------
	/**
	 * Add multiple inputs to the input queue
	 *
	 * @param $arr
	 *
	 * @return bool
	 */
	function add_inputs( $arr ) {

		if ( ! is_array( $arr ) ) {
			return false;
		}

		foreach ( $arr as $field ) {
			$this->add_input(
				$field[0], isset( $field[1] ) ? $field[1] : '',
				isset( $field[2] ) ? $field[2] : ''
			);
		}

		return true;
	}

	//--------------------------------------------------------------------------------
	/**
	 * Build the HTML for the form based on the input queue
	 *
	 * @param bool $echo Should the HTML be echoed or returned?
	 *
	 * @return string
	 */
	function build_form( $echo = true ) {

		global $s;

		$output = '';

		if ( $this->form['form_element'] ) {
			$output .= '<form method="' . $this->form['method'] . '"';

			if ( ! empty( $this->form['enctype'] ) ) {
				$output .= ' enctype="' . $this->form['enctype'] . '"';
			}

			if ( ! empty( $this->form['action'] ) ) {
				$output .= ' action="' . $this->form['action'] . '"';
			}

			if ( ! empty( $this->form['id'] ) ) {
				$output .= ' id="' . $this->form['id'] . '"';
			}

			if ( count( $this->form['class'] ) > 0 ) {
				$output .= $this->_output_classes( $this->form['class'] );
			}

			if ( $this->form['novalidate'] ) {
				$output .= ' novalidate';
			}

			$output .= '>';
		}

		// Add honeypot anti-spam field
		if ( $this->form['add_honeypot'] ) {
			$this->add_input( 'Leave blank to submit', array(
				'name'			 => 'honeypot',
				'slug'			 => 'honeypot',
				'id'			   => 'form_honeypot',
				'wrap_tag'		 => 'div',
				'wrap_class'	   => array( 'form_field_wrap', 'hidden' ),
				'wrap_id'		  => '',
				'wrap_style'	   => 'display: none',
				'request_populate' => false
			) );
		}

		// Add a WordPress nonce field
		if ( $this->form['add_nonce'] && function_exists( 'wp_create_nonce' ) ) {
			$this->add_input( 'WordPress nonce', array(
				'value'			=> wp_create_nonce( $this->form['add_nonce'] ),
				'add_label'		=> false,
				'type'			 => 'hidden',
				'request_populate' => false
			) );
		}

		// Iterate through the input queue and add input HTML
		foreach ( $this->inputs as $val ) :
			
			$min_max_range = $element = $end = $attr = $field = $label_html = '';

			// Automatic population of values using $_REQUEST data
			if ( $val['request_populate'] && isset( $_REQUEST[ $val['name'] ] ) ) {

				// Can this field be populated directly?
				if ( ! in_array( $val['type'], array( 'html', 'title', 'radio', 'checkbox', 'select', 'submit' ) ) ) {
					$val['value'] = $_REQUEST[ $val['name'] ];
				}
			}

			// Automatic population for checkboxes and radios
			if (
				$val['request_populate'] &&
				( $val['type'] == 'radio' || $val['type'] == 'checkbox' ) &&
				empty( $val['options'] )
			) {
				$val['checked'] = isset( $_REQUEST[ $val['name'] ] ) ? true : $val['checked'];
			}

			switch ( $val['type'] ) {

				case 'html':
					$element = '';
					$end	 = $val['label'];
					break;

				case 'title':
					$element = '';
					$end	 = '
					<h3>' . $val['label'] . '</h3>';
					break;

				case 'textarea':
					$element = 'textarea';
					$end	 = '>' . $val['value'] . '</textarea>';
					break;

				case 'dropdown':
				case 'select':
					$element = 'select';
					$end	 .= '>';
					if($val['value'] != '') {
						$end = " selvalue='{$val['value']}'" . $end;
					}
					foreach ( $val['options'] as $key => $opt ) {
						$opt_insert = '';
						if (
							// Is this field set to automatically populate?
							$val['request_populate'] &&

							// Do we have $_REQUEST data to use?
							isset( $_REQUEST[ $val['name'] ] ) &&

							// Are we currently outputting the selected value?
							$_REQUEST[ $val['name'] ] === $key
						) 
						{
							$opt_insert = ' selected';

						// Does the field have a default selected value?
						} 
						else if ( $val['selected'] === $key ) 
						{
							$opt_insert = ' selected';
						}
						$end .= '<option value="' . $key . '"' . $opt_insert . '>' . $opt . '</option>';
					}
					$end .= '</select>';
					break;

				/* BRM 
				flag type added. Actual checkboxes and handling are built by js, further down.
				*/
				case 'flags':
					$element = 'div';
					$end	 = '>' . $val['value'] . '</div>';
					$flagObj = [];
					$flagObj['id'] = $val['name'];
					$flagObj['values'] = json_encode($val['options']);
					$this->flags[] = $flagObj;
					break;


				case 'radio':
				case 'checkbox':

					// Special case for multiple check boxes
					if ( count( $val['options'] ) > 0 ) :
						$element = '';
						
						
						foreach ( $val['options'] as $key => $opt ) {
							$slug = $this->_make_slug( $opt );
							$end .= sprintf(
								'<input type="%s" name="%s[]" value="%s" id="%s"',
								$val['type'],
								$val['name'],
								$key,
								$slug
							);
							if (
								// Is this field set to automatically populate?
								$val['request_populate'] &&

								// Do we have $_REQUEST data to use?
								isset( $_REQUEST[ $val['name'] ] ) &&

								// Is the selected item(s) in the $_REQUEST data?
								in_array( $key, $_REQUEST[ $val['name'] ] )
							) {
								$end .= ' checked';
							}
							$end .= $this->field_close();
							$end .= ' <label for="' . $slug . '">' . $opt . '</label>';
						}
						$label_html = '<div class="checkbox_header">' . $val['label'] . '</div>';
						break;
					endif;
				
				// Used for all text fields (text, email, url, etc), single radios, single checkboxes, and submit
				default :
					$element = 'input';
					$end .= ' type="' . $val['type'] . '" value="' . $val['value'] . '"';
					/* BRM
					See validateas notes, above: (1)
					*/
					if($val['validateas'] != ''){
						$end .= ' validateas="' . $val['validateas'] . '"';
					} 
					if($val['pattern'] != ''){
						$end .= ' pattern="' . $val['pattern'] . '"';
					} 
					if($val['size'] != ''){
						$end .= ' size="' . $val['size'] . '"';
					}
					if($val['maxlength'] != ''){
						$end .= ' maxlength="' . $val['maxlength'] . '"';
					}
					if($val['placeholder'] != ''){
						$end .= ' placeholder="' . $val['placeholder'] . '"';
					}
					$end .= $val['checked'] ? ' checked' : '';
					$end .= $this->field_close();
					break;

			}

			// Added a submit button, no need to auto-add one
			if ( $val['type'] === 'submit' ) {
				$this->has_submit = true;
			}

			// Special number values for range and number types
			if ( $val['type'] === 'range' || $val['type'] === 'number' ) {
				$min_max_range .= ! empty( $val['min'] ) ? ' min="' . $val['min'] . '"' : '';
				$min_max_range .= ! empty( $val['max'] ) ? ' max="' . $val['max'] . '"' : '';
				$min_max_range .= ! empty( $val['step'] ) ? ' step="' . $val['step'] . '"' : '';
			}

			// Add an ID field, if one is present
			$id = ! empty( $val['id'] ) ? ' id="' . $val['id'] . '"' : '';

			// Output classes
			$class = $this->_output_classes( $val['class'] );

			// Special HTML5 fields, if set
			$attr .= $val['autofocus'] ? ' autofocus' : '';
			$attr .= $val['checked'] ? ' checked' : '';
			$attr .= $val['required'] ? ' required' : '';

			// Build the label
			if ( ! empty( $label_html ) ) {
				$field .= $label_html;
			} elseif ( $val['add_label'] && ! in_array( $val['type'], array( 'hidden', 'submit', 'title', 'html' ) ) ) {
				if ( $val['required'] ) {
					$val['label'] .= ' <strong>*</strong>';
				}
				$field .= '<label for="' . $val['id'] . '">' . $val['label'] . '</label>';
			}

			// An $element was set in the $val['type'] switch statement above so use that
			if ( ! empty( $element ) ) {
				if ( $val['type'] === 'checkbox' ) {
					$field = '
					<' . $element . $id . ' name="' . $val['name'] . '"' . $min_max_range . $class . $attr . $end .
							 $field;
				} else {
					$field .= '
					<' . $element . $id . ' name="' . $val['name'] . '"' . $min_max_range . $class . $attr . $end;
				}
			// Not a form element
			} else {
				$field .= $end;
			}

			// Parse and create wrap, if needed
			if ( $val['type'] != 'hidden' && $val['type'] != 'html' ) :

				$wrap_before = $val['before_html'];
				if ( ! empty( $val['wrap_tag'] ) ) {
					$wrap_before .= '<' . $val['wrap_tag'];
					$wrap_before .= count( $val['wrap_class'] ) > 0 ? $this->_output_classes( $val['wrap_class'] ) : '';
					$wrap_before .= ! empty( $val['wrap_style'] ) ? ' style="' . $val['wrap_style'] . '"' : '';
					$wrap_before .= ! empty( $val['wrap_id'] ) ? ' id="' . $val['wrap_id'] . '"' : '';
					if ( $val['group'] != '' ){
						$wrap_before .= ' group="' . $val['group'] . '"';
					}
					$wrap_before .= '>';
				}

				$wrap_after = $val['after_html'];
				if ( ! empty( $val['wrap_tag'] ) ) {
					$wrap_after = '</' . $val['wrap_tag'] . '>' . $wrap_after;
				}

				$output .= $wrap_before . $field . $wrap_after;
			else :
				$output .= $field;
			endif;

		endforeach;

		// Auto-add submit button
		if ( ! $this->has_submit && $this->form['add_submit'] ) {
			$output .= '<div class="form_field_wrap"><input type="submit" value="Submit" name="submit"></div>';
		}

		// Close the form tag if one was added
		if ( $this->form['form_element'] ) {
			$output .= '</form>';
		}

		/* BRM */
		$output .= $this->insertJavascript();

		// Output or return?
		if ( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}


	// Easy way to auto-close fields, if necessary
	function field_close() {
		return $this->form['markup'] === 'xhtml' ? ' />' : '>';
	}

	//--------------------------------------------------------------------------------
	// Validates id and class attributes
	// TODO: actually validate these things
	private function _check_valid_attr( $string ) {

		$result = true;

		// Check $name for correct characters
		// "^[a-zA-Z0-9_-]*$"

		return $result;

	}

	//--------------------------------------------------------------------------------
	// Create a slug from a label name
	private function _make_slug( $string ) {

		$result = '';

		$result = str_replace( '"', '', $string );
		$result = str_replace( "'", '', $result );
		$result = str_replace( '_', '-', $result );
		$result = preg_replace( '~[\W\s]~', '-', $result );

		$result = strtolower( $result );

		return $result;

	}

	//--------------------------------------------------------------------------------
	// Parses and builds the classes in multiple places
	private function _output_classes( $classes ) {

		$output = '';

		
		if ( is_array( $classes ) && count( $classes ) > 0 ) {
			$output .= ' class="';
			foreach ( $classes as $class ) {
				$output .= $class . ' ';
			}
			$output .= '"';
		} else if ( is_string( $classes ) ) {
			$output .= ' class="' . $classes . '"';
		}

		return $output;
	}

	//--------------------------------------------------------------------------------
	/* BRM
	To avoid collisions, the javascript (below) is namespaced and given a long, random name
	To avoid inadvertantly creating obvious profanity (stay focused, people!) 
		vowels and the letter K	are omitted.
	*/
	function generateRandomString($length = 10) {
		$characters = '0123456789bcdfghjlmnpqrstvwxyzBCDFGHJLMNPQRSTVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = 'phpFB'; // always start the id with a letter
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	//--------------------------------------------------------------------------------
	/* BRM
	When building the form from the tables, a database object is passed in
		and stored for possible use. We'll clean that up, here.
	*/
	function __destruct()
	{
		unset($this->db);
	}

	//--------------------------------------------------------------------------------
	/*  BRM
	PURPOSE: the javascript takes fields with the "group" attribute and wraps then
		in a fieldset. The "group" attribute becomes the legend.
	*/
	function insertJavascript(){
		global $s;
		if(count($this->flags) > 0) {
			$objFlags = json_encode($this->flags);
		} else {
			$objFlags = "[]";
		}
		$f = $this->form[id];
		$objectName = $this->generateRandomString(20);
		$out = file_get_contents(__DIR__ . '\\phpFormBuilder.js');
		$out = str_replace('${objFlags}', $objFlags, $out);
		$out = str_replace('${objectname}', $objectName, $out);
		$out = str_replace('${f}', $f, $out);
		$out = "<script>$out</script>";
		return $out;
	}

	//--------------------------------------------------------------------------------
	/* BRM
	This routine builds the form based upon groups and fields stored in a database.
	*/
	function getForm($idform, $db) {
		$db->execute("use applications"); // change this to match your own schema
		$rs = $db->execute("select * from vwFormBuilder where idform = $idform order by orderby", 1);
		
		// store for later use
		$this->db = $db;

		$this->set_att('action', $rs[0]['formAction']);

		$method = $rs[0]['formMethod'];
		$this->set_att('method', $method);

		$this->set_att('enctype', 'multipart/form-data');
		$this->set_att('markup', 'html');

		$autofocus = true;

		foreach($rs as $item)
		{
			ksort($item);
			$item = array_change_key_case($item);
			$item['pattern'] = ''; // TODO add pattern to the form properties
			$caption = $item['caption'];
			$attr = [];
			
			$attr['group'] = $item['groupname'];
			$attr['value'] = $this->getDataValue(strtolower($item['mapsto']));
			if(preg_match('/(dropdown|select)/', $item['controlType'])!==false)
			{
				$attr['selected'] = $attr['value'];
			}
			$this->addAttributeIfExists($attr, 'size'		, $item['isize']);
			$this->addAttributeIfExists($attr, 'maxlength'	, $item['imaxlength']);
			$this->addAttributeIfExists($attr, 'placeholder', $item['placeholder']);
			$this->addAttributeIfExists($attr, 'type'		, $item['controltype']);
			$this->addAttributeIfExists($attr, 'required'	, ($item['brequired'] == '1'));
			$this->addAttributeIfExists($attr, 'validateas'	, $item['validateas']);
			$this->addAttributeIfExists($attr, 'pattern'	, $item['pattern']);
			if ( stripos(',select,dropdown,flags,', $item['controltype']) !== false ) {
				if($item['valueslistsql'] != '') {
					$this->addAttributeIfExists($attr, 'options'	, $this->getOptionsList($item['valueslistsql']));
				}
				// TODO add options from explicit json list.
			}
			
			if ($autofocus)
			{
				// give autofocus to the first element in the form.
				$this->addAttributeIfExists($attr, 'autofocus', true);
				$autofocus = !$autofocus;

			}
			$this->add_input($item['caption'], $attr, $item['fieldname']);
		}

	}

	//--------------------------------------------------------------------------------
	/* BRM 
	this keeps us from adding attributes we don't need 
		without any fancy checks in the loop above
		and prevents adding a value more than once
	*/
	private function addAttributeIfExists(&$attr, $attrkey, $itemvalue) {
		if($itemvalue != '' && ! array_key_exists($attrkey, $attr)){
			$attr[$attrkey] = $itemvalue;
		}
	}

	//--------------------------------------------------------------------------------
	/* BRM
	When the form is populated with data, all array keys are lowercased 
	(so are the "mapto" values) so we don't have to think about it.
	*/
	function letData($s){
		$s = array_change_key_case($s);
		$this->data = $s;
	}

	//--------------------------------------------------------------------------------
	private function getDataValue($s){
		if(count($this->data) > 0 && $s != '') {
			if(array_key_exists($s, $this->data)){
				return $this->data[$s];
			}
		}
		return '';
	}

	//--------------------------------------------------------------------------------
	/* BRM
	Gets dropdown options and flags values. In my case, I needed to pull options
	from a different schema than the form, so to do that I pass in both queries
	separated by a semi-colon:

	use different_schema;
	select stuff from table

	The function splits on the semi-colon, executes for a recordset each time
	and returns the first one it finds.
	
	*/
	private function getOptionsList($sql){
		if($sql == '') return;
		$arr = explode(";", $sql);
		foreach($arr as $s) {
			$rs = $this->db->execute($s, 1);
			// first recordset to return a value wins.
			if(count($rs) > 0) {
				return $rs;
			}
		}
	}
}

