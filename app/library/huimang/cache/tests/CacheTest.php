<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/15 15:51
 * @copyright: 2017@firegit.com
 * @filesource: CacheTest.php
 */

namespace huimang\cache\tests;


use huimang\cache\Cache;


class CacheTest extends \PHPUnit_Framework_TestCase
{
    public function testCache()
    {
        $cache = Cache::get();
        $cache->set('test', 'hello');
        $value = $cache->get('test');
        $this->assertEquals('hello', $value);

        $cache->set('foo', 'bar', 1);
        sleep(2);
        $value = $cache->get('foo');
        $this->assertEquals(null, $value);

        $cache->del('test');
        $this->assertEquals(null, $cache->get('test'));
    }

}
