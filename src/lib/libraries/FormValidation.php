<?php
/*
 * Created by xsh on Mar 6, 2014 
 * 
 * 支持针对验证条件返回多语言提示信息。
 * 
 * CI_Form_validation modified log
 * 		line 529: add $this->_field_data[$row['field']]['error_rule'] = $rule;
 * 		line 678: add $this->_field_data[$row['field']]['error_rule'] = $rule;
 */


 
require_once (BASEPATH.'../ci/libraries/Form_validation.php'); 
 
class FormValidation extends CI_Form_validation
{
	public function __construct($rules = array()){
		parent::__construct($rules);	
	}
	
	public function set_rules($field, $label = '', $rules = '', $messages = ''){
		// No reason to set rules if we have no POST data
		if (count($_POST) == 0)
		{
			return $this;
		}

		// If an array was passed via the first parameter instead of indidual string
		// values we cycle through it and recursively call this function.
		if (is_array($field))
		{
			foreach ($field as $row)
			{
				// Houston, we have a problem...
				if ( ! isset($row['field']) OR ! isset($row['rules']))
				{
					continue;
				}

				// If the field label wasn't passed we use the field name
				$label = ( ! isset($row['label'])) ? $row['field'] : $row['label'];
				$messages = ( isset($row['messages'])) ? $row['messages'] : '';

				// Here we go!
				$this->set_rules($row['field'], $label, $row['rules'], $row['messages']);
			}
			return $this;
		}

		// No fields? Nothing to do...
		if ( ! is_string($field) OR  ! is_string($rules) OR $field == '')
		{
			return $this;
		}

		// If the field label wasn't passed we use the field name
		$label = ($label == '') ? $field : $label;

		// Is the field name an array?  We test for the existence of a bracket "[" in
		// the field name to determine this.  If it is an array, we break it apart
		// into its components so that we can fetch the corresponding POST data later
		if (strpos($field, '[') !== FALSE AND preg_match_all('/\[(.*?)\]/', $field, $matches))
		{
			// Note: Due to a bug in current() that affects some versions
			// of PHP we can not pass function call directly into it
			$x = explode('[', $field);
			$indexes[] = current($x);

			for ($i = 0; $i < count($matches['0']); $i++)
			{
				if ($matches['1'][$i] != '')
				{
					$indexes[] = $matches['1'][$i];
				}
			}

			$is_array = TRUE;
		}
		else
		{
			$indexes	= array();
			$is_array	= FALSE;
		}

		// Build our master array
		$this->_field_data[$field] = array(
			'field'				=> $field,
			'label'				=> $label,
			'rules'				=> $rules,
			'is_array'			=> $is_array,
			'keys'				=> $indexes,
			'postdata'			=> NULL,
			'error'				=> '',
			'messages'			=> $messages
		);

		return $this;
	}
	
	/* rules:
	 * $validation_rules = array(
			array(
				'field' => 'password',
				'rules' => 'required|min_length[5]',
				'messages' =>array(
					'required'=>'lang:resetPwd_password_empty',
					'min_length' => 'lang:resetPwd_password_range'
				)
			)
		); 
		
		return: [{'field'=>'password', message=>'password is required'}]
	 */
	
	public function error_result(){
		$error = array();
		
		if(count( $this->_error_array ) == 0 )
			$error;
		
		foreach($this->_error_array as $k => $v){
			$error_rule = $this->_field_data[$k]['error_rule'];
			
			if(isset($this->_field_data[$k]['messages'])){
				$messages = $this->_field_data[$k]['messages'];
				
				if(is_string($messages))
					$message = $this->_translate_fieldname($messages);
				else
					$message =  $this->_translate_fieldname($messages[$error_rule]);
					
				$error[] = array('field'=> $k, 'message'=> $message);
			}else{
				$error[] = array('field'=> $k, 'message'=> $this->$v);
			}
		}
		
		return $error;
	}
}
?>
