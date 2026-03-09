<?php
namespace libs\form;

/**
 *
 * Class Form 表单元素生成
 * @from https://github.com/illuminate/html
 * @package libs
 */
class FormBuilder
{

    protected static $instance;

    /**
     * An array of label names we've created.
     *
     * @var array
     */
    protected $labels = array();

    /**
     * The types of inputs to not fill values on by default.
     *
     * @var array
     */
    protected $skipValueTypes = array('file', 'password', 'checkbox', 'radio');

    /**
     * 单例模式
     * @param array $options
     * @return static
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    /**
     * Build an HTML attribute string from an array.
     *
     * @param  array  $attributes
     * @return string
     */
    public function attributes($attributes)
    {
        $html = array();

        // For numeric keys we will assume that the key and the value are the same
        // as this will convert HTML attributes such as "required" to a correct
        // form like required="required" instead of using incorrect numerics.
        foreach ((array) $attributes as $key => $value) {
            $element = $this->attributeElement($key, $value);

            if ( ! is_null($element)) $html[] = $element;
        }

        return count($html) > 0 ? ' '.implode(' ', $html) : '';
    }

    /**
     * Build a single attribute element.
     *
     * @param  string  $key
     * @param  string  $value
     * @return string
     */
    protected function attributeElement($key, $value)
    {
        if (is_numeric($key)) $key = $value;

        if ( ! is_null($value)){
            // 判断是否json
            $jsonData = json_decode($value, true);
            if (is_array($jsonData) && !empty($jsonData)) {
                return $key.'=\''.e($value).'\'';
            } else {
                return $key.'="'.e($value).'"';
            }

        }

        return '';
    }

    /**
     * Get the value that should be assigned to the field.
     *
     * @param  string  $name
     * @param  string  $value
     * @return string
     */
    public function getValueAttribute($name, $value = null)
    {
        if (is_null($name)) {
            return $value;
        }

        if (!is_null($value)) {
            return $value;
        }
        return $value;
    }

    /**
     * Get the ID attribute for a field name.
     *
     * @param  string  $name
     * @param  array   $attributes
     * @return string
     */
    public function getIdAttribute($name, $attributes)
    {
        if (array_key_exists('id', $attributes))
        {
            return $attributes['id'];
        }

        if (in_array($name, $this->labels))
        {
            return $name;
        }

        return '';
    }

	/**
	 * Create a select box field.
	 *
	 * @param  string  $name
	 * @param  array   $list
	 * @param  string  $selected
	 * @param  array   $options
	 * @return string
	 */
	public function select($name, $list = array(), $selected = null, $options = array())
	{
		// When building a select box the "value" attribute is really the selected one
		// so we will use that when checking the model or session for a value which
		// should provide a convenient method of re-populating the forms on post.
		$selected = $this->getValueAttribute($name, $selected);

		$options['id'] = $this->getIdAttribute($name, $options);

		if ( ! isset($options['name'])) $options['name'] = $name;

		// We will simply loop through the options and build an HTML value for each of
		// them until we have an array of HTML declarations. Then we will join them
		// all together into one single HTML element that can be put on the form.
		$html = array();

		foreach ($list as $value => $display)
		{
			$html[] = $this->getSelectOption($display, $value, $selected);
		}

		// Once we have all of this HTML, we can join this into a single element after
		// formatting the attributes into an HTML "attributes" string, then we will
		// build out a final select statement, which will contain all the values.
		$options = $this->attributes($options);

		$list = implode('', $html);

		return "<select{$options}>{$list}</select>";
	}

    /**
     * Create a form input field.
     *
     * @param  string  $type
     * @param  string  $name
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function input($type, $name, $value = null, $options = array())
    {
        if ( ! isset($options['name'])) $options['name'] = $name;

        // We will get the appropriate value for the given field. We will look for the
        // value in the session for the value in the old input data then we'll look
        // in the model instance if one is set. Otherwise we will just use empty.
        $id = $this->getIdAttribute($name, $options);

        if (!in_array($type, $this->skipValueTypes))
        {
            $value = $this->getValueAttribute($name, $value);
        }

        // Once we have the type, value, and ID we can merge them into the rest of the
        // attributes array so we can convert them into their HTML attribute format
        // when creating the HTML element. Then, we will return the entire input.
        $merge = compact('type', 'value', 'id');

        $options = array_merge($options, $merge);

        return '<input'.$this->attributes($options).'>';
    }

    /**
     * Create a form textarea field.
     * @param $name
     * @param null $value
     * @param array $options
     * @return string
     */
    public function textarea($name, $value = null, $options = array())
    {
        if ( ! isset($options['name'])) $options['name'] = $name;
        $id = $this->getIdAttribute($name, $options);
        $options = array_merge($options, compact('id'));

        return '<textarea'.$this->attributes($options).'>'.$value.'</textarea>';
    }

    /**
     * Create a form label element.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function label($name, $value = null, $options = array())
    {
        $this->labels[] = $name;

        $options = $this->attributes($options);

        $value = e($this->formatLabel($name, $value));

        return '<label for="'.$name.'"'.$options.'>'.$value.'</label>';
    }

    /**
     * 生成 radio 组
     * @param string $name 即标签name属性
     * @param string $values 一维数组，例如：['1'=>'菜单','0'=>'权限认证']
     * @param null $checked 设置选中的值
     * @param array $options 其他属性,key-属性名，value-属性值，['input'=>[key=>$value],'label'=>[key=>$value]]
     * @return string
     */
    public function radios($name, $values, $checked=null, $options = array())
    {
        if (is_array($values)) {
            $label = $input = [];
            if (isset($options['label'])) {
                $label = $options['label'];
            }
            if (isset($options['input'])) {
                $input = $options['input'];
            }
            $html = '';
            $checked = is_null($checked) ? key($values) : $checked;
            foreach ($values as $k=>$v) {
                if ($checked==$k) {
                    $input['checked']='checked';
                }
                $html .= '<div class="radio-item">'.$this->input('radio', $name, $k, array_merge(['id'=>$name.'-'.$k],$input));
                $html .= $this->label($name.'-'.$k, $v, $label).'</div>';
                unset($input['checked']);
            }

            return "<div class=\"radio-group\">{$html}</div>";
        }
        return '';
    }

    /**
     * Get the select option for the given value.
     *
     * @param  string  $display
     * @param  string  $value
     * @param  string  $selected
     * @return string
     */
    public function getSelectOption($display, $value, $selected)
    {
        if (is_array($display))
        {
            return $this->optionGroup($display, $value, $selected);
        }

        return $this->option($display, $value, $selected);
    }

    /**
     * Determine if the value is selected.
     *
     * @param  string  $value
     * @param  string  $selected
     * @return string
     */
    protected function getSelectedValue($value, $selected)
    {
        if (is_array($selected))
        {
            return in_array($value, $selected) ? 'selected' : null;
        }

        return ((string) $value == (string) $selected) ? 'selected' : null;
    }

    /**
     * Format the label value.
     *
     * @param  string  $name
     * @param  string|null  $value
     * @return string
     */
    protected function formatLabel($name, $value)
    {
        return $value ?: ucwords(str_replace('_', ' ', $name));
    }

    /**
     * Create a select element option.
     *
     * @param  string  $display
     * @param  string  $value
     * @param  string  $selected
     * @return string
     */
    protected function option($display, $value, $selected)
    {
        $selected = $this->getSelectedValue($value, $selected);

        $options = array('value' => e($value), 'selected' => $selected);

        return '<option'.$this->attributes($options).'>'.e($display).'</option>';
    }

    /**
     * Create an option group form element.
     *
     * @param  array   $list
     * @param  string  $label
     * @param  string  $selected
     * @return string
     */
    protected function optionGroup($list, $label, $selected)
    {
        $html = array();

        foreach ($list as $value => $display)
        {
            $html[] = $this->option($display, $value, $selected);
        }

        return '<optgroup label="'.e($label).'">'.implode('', $html).'</optgroup>';
    }
}