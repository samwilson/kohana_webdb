<?php defined('SYSPATH') or die('No direct script access.');
/**
 * A library of array manipulation functions.
 *
 * @package  WebDB
 * @category Helpers
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_Arr extends Arr
{

	/**
	 * Find the Longest Common Prefix of two strings.
	 *
	 * @param string $str1
	 * @param string $str2
	 * @return string The LCP.
	 */
	public static function lcp($str1, $str2)
    {
		$prefix = "";
		for ($l=0; $l<=min(strlen($str1), strlen($str2)); $l++)
		{
			$substr = substr($str1, 0, $l);
			if ($substr == substr($str2, 0, $l))
			{
				$prefix = $substr;
			} else
			{
				break;
			}
		}
		return $prefix;
    }

	/**
	 * Find all LCPs (over a certain length) in an array.
	 *
	 * This is a bit like phpMyAdmin's `List_Database::getGroupedDetails()`
	 * method, except that one only uses the *first* underscore as the prefix
	 * separator; here, we use the maximum length possible (but that still ends
	 * in an underscore).
	 *
	 * @param array $arr
	 * @param integer $min_length
	 * @return array[string]
	 */
	static public function get_prefix_groups($arr, $min_length = 4)
	{
		$out = array();
		asort($arr);
		$arr = array_values($arr);
		for ($str1_idx=0; $str1_idx<count($arr); $str1_idx++)
		{
			for ($str2_idx=0; $str2_idx<count($arr); $str2_idx++)
			{

				$str1 = $arr[$str1_idx];
				$str2 = $arr[$str2_idx];

				$lcp = Webdb_Arr::lcp($str1, $str2);
				//echo "<li>$str1, $str2 -- $lcp";

				// $prev is the length of the LCP of: (str1, and the element before str1 if there is one).
                $prev = 0; 
                if (isset($arr[$str1_idx - 1])) {
                    $prev_lcp = Webdb_Arr::lcp($str1, $arr[$str1_idx - 1]);
                    $prev = strlen($prev_lcp);
                }

				// $next is the length of the LCP of: (str1, and the element after str1 if there is one).
                $next = 0;
                if (isset($arr[$str1_idx + 1])) {
                    $next_lcp = Webdb_Arr::lcp($str1, $arr[$str1_idx + 1]);
                    $next = strlen($next_lcp);
                }

                // 'is_other' and 'is_self' are with respect to str1 and str2.
                $is_long_enough = strlen($lcp) > $min_length;
                $is_self = $lcp == $str1;
                $has_common_neighbours = $prev > $min_length || $next > $min_length;
                $is_superstring_of_prev = $prev > $min_length && strpos($lcp, $prev_lcp)!==FALSE;
                $is_superstring_of_next = $next > $min_length && strpos($lcp, $next_lcp)!==FALSE;
                $not_in_result = !in_array($lcp, $out);
                $is_superstring_of_existing = FALSE;
				foreach ($out as $i)
				{
                    if (strpos($lcp, $i)!==FALSE && strlen($lcp) > $min_length)
					{
                        $is_superstring_of_existing = TRUE;
                        continue;
                    }
                }
				$ends_in_underscore = substr($lcp, -1) == '_';

				// Put it all together.
                if ($is_long_enough && $not_in_result && !$is_superstring_of_existing && $ends_in_underscore
					&& ((!$is_self && $has_common_neighbours && !$is_superstring_of_prev && $is_superstring_of_next)
						|| ($is_self && !$has_common_neighbours && !$is_superstring_of_prev && !$is_superstring_of_next)
						|| (!$is_self && $has_common_neighbours && $is_superstring_of_prev && $is_superstring_of_next))
					)
				{
                    $out[] = $lcp;
				}
			}
		}
		//exit(Kohana::debug($arr).Kohana::debug($out));
		return $out;
	}

}