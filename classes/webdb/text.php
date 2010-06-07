<?php defined('SYSPATH') or die('No direct script access.');
/**
 * A library of text manipulation functions.
 *
 * @package  WebDB
 * @category Base
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_Text extends Text
{

	/**
	 *
	 * Apply the titlecase filter to a string: removing underscores, uppercasing
	 * initial letters, and performing a few common (and not-so-common) word
	 * replacements such as initialisms and punctuation.
	 *
	 * @param string|array $value    The underscored and lowercase string to be
	 *                               titlecased, or an array of such strings.
	 * @param 'html'|'latex' $format The desired output format.
	 * @return string                A properly-typeset title.
	 * @todo Get replacement strings from configuration file.
	 */
	public static function titlecase($value, $format='html')
	{

		/**
		 * The mapping of words (and initialisms, etc.) to their titlecased
		 * counterparts for HTML output.
		 * @var array
		 */
		$html_replacements = array(
				'id'     => 'ID',
				'cant'   => "can't",
				'in'     => 'in',
				'at'     => 'at',
				'of'     => 'of',
				'for'    => 'for',
				'sql'    => 'SQL',
				'todays' => "Today's",
		);

		/**
		 * The mapping of words (and initialisms, etc.) to their titlecased
		 * counterparts for LaTeX output.
		 * @var array
		 */
		$latex_replacements = array(
				'cant' => "can't",
		);

		/**
		 * Marshall the correct replacement strings.
		 */
		if ($format=='latex')
		{
			$replacements = array_merge($html_replacements, $latex_replacements);
		} else
		{
			$replacements = $html_replacements;
		}

		/**
		 * Recurse if neccessary
		 */
		if (is_array($value))
		{
			return array_map(array('Text', 'titlecase'), $value);
		} else
		{
			$out = ucwords(preg_replace('|_|',' ', $value));
			foreach ($replacements as $search=>$replacement)
			{
				$out = preg_replace("|\b$search\b|i", $replacement, $out);
			}
			return $out;
		}
		return $out;
	}
}
