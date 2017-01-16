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
use huimang\Exception;

class Repository
{
    const STANDARD_COMMIT_FORMAT = '%H %at %an %s';

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
     * @return array
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

    /**
     * 列举分支
     * @param string $path
     * @return array [$branch => $hash]
     */
    public static function lsBranches(string $path)
    {
        return self::lsRefs($path, 'heads');
    }

    /**
     * 列举标签
     * @param string $path
     * @return array
     */
    public static function lsTags(string $path)
    {
        return self::lsRefs($path, 'tags');
    }

    /**
     * 将分支正常化
     * @param $branch
     * @return string
     */
    public static function normalBranch($branch)
    {
        if (preg_match('#[0-9a-z]{40}#', $branch)) {
            return $branch;
        }
        return 'refs/heads/' . $branch;
    }

    /**
     * 列出提交
     * @param string $path
     * @param string $startHash
     * @param $num
     * @return array ['commits' => [], 'next' => string|false]
     */
    public static function lsCommits(string $path, string $startHash, $num)
    {
        chdir($path);
        $cmd = new Command(
            'git log --oneline -%d %s --format="%s"',
            $num + 1,
            self::normalBranch($startHash),
            self::STANDARD_COMMIT_FORMAT
        );
        $commits = [];
        $cmd->execute();
        foreach ($cmd->outputs as $line) {
            $commits[] = self::handleCommits($line);
        }
        $next = false;
        if ($commits) {
            $next = array_pop($commits)['hash'];
        }
        return [
            'commits' => $commits,
            'next' => $next,
        ];
    }

    private static function handleCommits($line)
    {
        $arr = explode(' ', $line, 4);
        return [
            'hash' => $arr[0],
            'time' => $arr[1],
            'author' => $arr[2],
            'msg' => isset($arr[3]) ? $arr[3] : '',
        ];
    }

    /**
     * 列举ref
     * @param string $path
     * @param $type
     * @return array
     */
    private static function lsRefs(string $path, $type)
    {
        chdir($path);
        $cmd = new Command('git show-ref --' . $type);
        $branches = [];
        $len = strlen('refs/heads/');
        $cmd->execute();
        foreach ($cmd->outputs as $line) {
            list($hash, $branch) = preg_split('#[\s\t]+#', $line);
            $branches[substr($branch, $len)] = [
                'name' => substr($branch, $len),
                'hash' => $hash,
            ];
        }
        if ($branches) {
            $cmd = new Command(
                "git rev-list %s --format='%s'",
                implode(' ', array_unique(array_column($branches, 'hash'))),
                self::STANDARD_COMMIT_FORMAT
            );
            $cmd->execute();
            foreach ($cmd->outputs as $line) {
                if (preg_match('#^commit [a-z0-9]{40}$#', $line)) {
                    continue;
                }
                $commit = self::handleCommits($line);
                foreach ($branches as $key => $branch) {
                    if ($branch['hash'] == $commit['hash']) {
                        $branches[$key] = array_merge($branch, $commit);
                        break;
                    }
                }
            }
        }

        ksort($branches);
        return $branches;
    }

    /**
     * 获取文件
     * @param string $path
     * @param string $branch
     * @param string $file
     * @return string
     * @throws Exception
     */
    public static function catFile(string $path, string $branch, string $file)
    {
        chdir($path);
        $cmd = new Command('git ls-tree refs/heads/%s %s', $branch, $file);
        $cmd->execute();
        if (empty($cmd->outputs)) {
            throw new Exception('repository.fileNotFound');
        }
        list($mod, $type, $hash) = preg_split('#\s+#', $cmd->outputs[0]);

        $cmd = new Command('git cat-file -p %s', $hash);
        $cmd->execute();
        return implode("\n", $cmd->outputs);
    }
}
