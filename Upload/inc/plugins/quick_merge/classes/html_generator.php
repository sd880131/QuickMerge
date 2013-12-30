<?php
/*
 * Wildcard Helper Classes
 * ACP - HTML Generator
 *
 * produces standard HTML
 */

class HTMLGenerator
{
	/*
	 * default URL for links, can be set in __construct()
	 * but can be also changed in-line if needed
	 */
	public $base_url = 'index.php';

	/*
	 * allowed keys and properties for standard tags
	 */
	public $allowed = array
	(
		"url_keys" => array
		(
			'action', 'mode', 'id'
		),
		"common" => array
		(
			'id', 'name', 'style', 'class'
		),
		"a" => array
		(
			'title', 'onclick', 'href'
		),
		"img" => array
		(
			'title', 'alt', 'onclick', 'src'
		)
	);

	/*
	 * __construct()
	 *
	 * @param - $url - (string) - the base URL for all links and URLs
	 * @param - $extra_keys - (mixed) - a string key name or an array of key names to allow
	 */
	public function __construct($url = '', $extra_keys = '')
	{
		// custom base URL?
		if(trim($url))
		{
			$this->base_url = trim($url);
		}

		// custom keys?
		if($extra_keys)
		{
			if(is_array($extra_keys))
			{
				foreach($extra_keys as $key)
				{
					$key = trim($key);
					if($key && !in_array($key, $this->allowed['url_keys']))
					{
						$this->allowed['url_keys'][] = $key;
					}
				}
			}
			else
			{
				$this->allowed['url_keys'][] = $extra_keys;
			}
		}
	}

	/*
	 * url()
	 *
	 * builds a URL from standard options array
	 *
	 * @param - $options - (array) keyed to standard URL options
	 * @param - $base_url - (string) overrides the default URL base if present
	 * @param - $encoded - (boolean) override URL encoded ampersand (for JS mostly)
	 */
	public function url($options = array(), $base_url = '', $encoded = true)
	{
		$url = $this->base_url;
		if(trim($base_url))
		{
			$url = $base_url;
		}

		$amp = '&';
		if($encoded)
		{
			$amp = '&amp;';
		}
		$sep = $amp;
		if(strpos($url, '?') === false)
		{
			$sep = '?';
		}

		// check for the allowed options
		foreach((array) $this->allowed['url_keys'] as $item)
		{
			if(isset($options[$item]) && $options[$item])
			{
				// and add them if set
				$url .= "{$sep}{$item}={$options[$item]}";
				$sep = $amp;
			}
		}
		return $url;
	}

	/*
	 * a()
	 *
	 * provides a short-cut for $this::link()
	 */
	public function a($url = '', $caption = '', $options = '', $icon_options = array())
	{
		return $this->link($url, $caption, $options, $icon_options);
	}

	/*
	 * link()
	 *
	 * builds an HTML anchor from the provided options
	 *
	 * @param - $url - (string) the address
	 * @param - $caption - (string) the innerHTML of the tag
	 * @param - $options - (array) options to effect the HTML output
	 * @param - $icon_options - (array) options for the icon IF specified in $options
	 */
	public function link($url = '', $caption = '', $options = '', $icon_options = array())
	{
		if(!$url)
		{
			$url = $this->url();
		}
		if(!isset($caption) || !$caption)
		{
			$caption = $url;
		}

		$options['href'] = $url;

		if(isset($options['icon']))
		{
			$icon_link = $this->build_tag('a', $this->img($options['icon'], $icon_options), $options);
		}

		return "{$icon_link}&nbsp;{$this->build_tag('a', $caption, $options)}";
	}

	/*
	 * img()
	 *
	 * generate HTML <img> mark-up
	 *
	 * @param - $url - (string) image source attribute
	 * @param - $options - (array) a keyed array of options to be generated
	 */
	public function img($url, $options = array())
	{
		$options['src'] = $url;

		return $this->build_tag('img', '', $options, true);
	}

	/*
	 * build_tag()
	 *
	 * build an HTML tag by name
	 *
	 * @param - $tag - (string) the type of HTML tag to be generated
	 * @param - $content (string) the innerHTML of the tag
	 * @param - $options - (array) a keyed array of options to be generated
	 * @param - $self_close - (bool) true to self-close tag (<br />), false for two-part tags (<a></a>)
	 */
	public function build_tag($tag, $content = '', $options = array(), $self_close = false)
	{
		$properties = $this->build_property_list($options, $this->allowed[$tag]);

		if($self_close == true)
		{
			return <<<EOF
<{$tag}{$properties}/>
EOF;
		}
		else
		{
			return <<<EOF
<{$tag}{$properties}>{$content}</{$tag}>
EOF;
		}
	}

	/*
	 * build_property()
	 *
	 * build a single property
	 *
	 * @param - $key - (string) the property name
	 * @param - $val - (mixed) the value
	 */
	public function build_property($key, $val)
	{
		return <<<EOF
 {$key}="{$val}"
EOF;
	}

	/*
	 * build_property_list()
	 *
	 * build a property list from an array of options matched against an allowed list (if it exists)
	 *
	 * @param - $options - (array) keyed array of properties
	 * @param - $allowed - (array) unindexed array of allowable property names
	 */
	public function build_property_list($options = array(), $allowed = array())
	{
		$property_list = '';

		$allowed = array_merge($this->allowed['common'], (array) $allowed);
		foreach($allowed as $key)
		{
			if(isset($options[$key]) && $options[$key])
			{
				$property_list .= $this->build_property($key, $options[$key]);
			}
		}
		return $property_list;
	}
}

?>
