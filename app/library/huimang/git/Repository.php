<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/15 11:49
 * @copyright: 2017@firegit.com
 * @filesource: Repository.php
 */
namespace huimang\git;

class Repository
{
    /**
     * 添加库
     * @param string $path
     * @param array $hooks
     */
    public static function addRepo(string $path, array $hooks = [])
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        chdir($path);
        exec('git init --bare');
        exec('git config http.receivepack true');
        exec('rm ./hooks/*');
        foreach ($hooks as $key => $content) {
            $file = "{$path}/hooks/{$key}";
            file_put_contents($file, $content);
            exec('chmod a+x ' . $file);
        }
    }
}
