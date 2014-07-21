<?php

class Gatuf_Form_Widget_HtmlareaInput extends Gatuf_Form_Widget {
	public function __construct($attrs=array()) {
		$this->attrs = array_merge(array('cols' => '80', 'rows' => '10'), $attrs);
	}
	
	public function render($name, $value, $extra_attrs=array()) {
		if ($value === null) $value = '';
		
		$final_attrs = $this->buildAttrs (array('name' => $name), $extra_attrs);
		
		$id = $extra_attrs['id'];
		
		$edit_area = array ();
		$edit_area[] = sprintf('<textarea%s>%s</textarea>', Gatuf_Form_Widget_Attrs($final_attrs), $value);
		
		$edit_area[] = '<script type="text/javascript">';
		$edit_area[] = '// <![CDATA[';
		$edit_area[] = sprintf ("new nicEditor({buttonList : ['bold', 'italic', 'underline', 'ol', 'ul', 'indent', 'outdent', 'hr', 'image', 'link', 'unlink'], iconsPath : '%s'}).panelInstance('%s');", Gatuf_Template_Tag_MediaUrl::url ('/img/gatuf/nicEditorIcons.png'), $id);
		$edit_area[] = '// ]]>';
		$edit_area[] = '</script>';
		
		return new Gatuf_Template_SafeString(implode("\n", $edit_area), true);
	}
}
