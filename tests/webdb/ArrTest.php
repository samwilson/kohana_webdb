<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Testing WebDB's array manipulation functions.
 *
 * @group    webdb
 * @package  WebDB
 * @category Tests
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_ArrTest extends Kohana_Unittest_Testcase
{

	public function test_lcp()
	{
		$str1 = 'test_lcp';
		$str2 = 'testing_lcp';
		$lcp = Webdb_Arr::lcp($str1, $str2);
		$this->assertSame($lcp, 'test', "LCP of '$str1' and '$str1' should be 'test' but is '$lcp'.");
	}

	public function test_no_lcp()
	{
		$str1 = 'test_lcp';
		$str2 = 'no_lcp_here';
		$lcp = Webdb_Arr::lcp($str1, $str2);
		$this->assertSame($lcp, '', "LCP of '$str1' and '$str1' should the empty string, but is '$lcp'.");
	}

	public function test_full_lcp()
	{
		$str1 = 'a_string';
		$str2 = 'a_string';
		$lcp = Webdb_Arr::lcp($str1, $str2);
		$this->assertSame($lcp, 'a_string', "LCP of '$str1' and '$str1' should be 'a_string', but is '$lcp'.");
	}

	public function test_get_prefix_groups()
	{
		$in = array(
			'Lorem ipsum dolor',
			'Maecenas ornare egestas',
			'ac',
			'magna. Phasellus dolor',
			'et',
			'sociis natoque penatibus',
			'ac mattis',
			'Nunc mauris. Morbi',
			'Lorem quam vel sapien',
			'eros.',
			'at, iaculis quis,',
			'Lorem ut',
			'dolor dolor, tempus',
			'Suspendisse non',
			'tellus',
			'Donec luctus',
			'neque',
			'Proin',
			'eget metus',
			'Curabitur ut odio',
			'dolor placerat eget,',
			'odio.',
			'varius ultrices, mauris',
			'a purus.',
			'urna',
			'felis, adipiscing'
		);
		$expected = array(
			'Lorem ',
			'dolor '
		);
		$actual = Webdb_Arr::get_prefix_groups($in);
		$this->assertSame($actual, $expected);
	}

}