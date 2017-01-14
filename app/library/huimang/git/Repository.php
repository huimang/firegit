<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/15 11:49
 * @copyright: 2017@firegit.com
 * @filesource: Repository.php
 */
namespace huimang\git;

use huimang\console\Command;

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

    /**
     * 列举目录tree
     * @param string $path
     * @param string $branch
     * @param string $dir
     */
    public static function lsTree(string $path, string $branch = 'master', string $dir = '')
    {
        chdir($path);
        $dir = trim($dir, '/');
        if (!$dir) {
            $dir = '.';
        } elseif ($dir[0] != '.') {
            $dir = './' . $dir;
        }
        $dir .= '/';
        $cmd = new Command('git ls-tree refs/heads/%s %s -l', $branch, $dir);
        $files = ['dir' => [], 'file' => []];
        $cmd->setOutputCallback(function ($line) use (&$files) {
            list($mode, $type, $hash, $size, $file) = preg_split('#\s+#', $line);
            if ($type == 'tree') {
                $files['dir'][] = [
                    'path' => $file,
                    'name' => basename($file),
                    'hash' => $hash
                ];
            } elseif ($type == 'blob') {
                $files['file'][] = [
                    'path' => $file,
                    'name' => basename($file),
                    'size' => $size,
                    'hash' => $hash,
                    'ext' => strtolower(pathinfo($file, PATHINFO_EXTENSION))
                ];
            }
        });
        $cmd->execute();
        array_multisort($files['dir'], array_column($files['dir'], 'name'));
        array_multisort($files['file'], array_column($files['file'], 'name'));
        return $files;
    }
}
