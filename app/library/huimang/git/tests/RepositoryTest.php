<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/15 14:11
 * @copyright: 2017@firegit.com
 * @filesource: RepositoryTest.php
 */

namespace huimang\git\tests;


use huimang\git\Repository;


class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateRepo()
    {
        $path = REPO_PATH . '/huimang/firegit.git';
        //Repository::addRepo($path);
        $files = Repository::lsTree($path, 'master', './');
        var_dump($files);
    }
}
