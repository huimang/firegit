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
     * @return array
     * [$branch => [
     *   'name',
     *   'hash',
     *   'time',
     *   'author',
     *   'msg',
     * ]]
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
     * 获取commit所在分支
     * @param string $path
     * @param string $commit
     * @return array
     * [$branch => $hash]
     */
    public static function lsBranchesByCommit(string $path, string $commit)
    {
        $inBranches = [];
        $branches = self::lsBranches($path);
        foreach ($branches as $branch) {
            // 检查是否在和某分支有共同祖先
            if (self::getCommonHash($path, $branch['hash'], $commit)) {
                $inBranches[] = $branch;
            }
        }
        return $inBranches;
    }

    /**
     * 获取两个commit的共同祖先
     * @param string $path
     * @param string $commit1
     * @param string $commit2
     * @return string|false
     */
    public static function getCommonHash(string $path, string $commit1, string $commit2)
    {
        chdir($path);
        $cmd = new Command(
            'git merge-base %s %s',
            self::normalBranch($commit1),
            self::normalBranch($commit2)
        );
        if (!$cmd->execute() && $cmd->outputs) {
            return $cmd->outputs[0];
        }
        return false;
    }

    /**
     * 获取提交信息
     * @param string $path
     * @param string $hash
     * @param int $type 0 只获取commit信息 1 获取commit的变化的文件
     * @return array
     * [
     *  'merge' => 是否为合并
     *  'mergeFrom' => 从哪个commit合并过来
     *  'tree' => tree的hash值
     *  'parent' => 上一个commit的hash值
     *  'author' => [
     *     'name' => 名称
     *     'email' => 邮件
     *     'date' => 日期
     *  ],
     *  'commiter' => [
     *     'name' => 名称
     *     'email' => 邮件
     *     'date' => 日期
     *   ],
     *  'stats' => [
     *     [
     *       'file' => 文件,
     *       'insect' => 插入行数
     *       'delete' => 删除行数
     *      ]
     *   ],
     *   'tstats' => [
     *      'file' => 影响文件数量
     *      'insect' => 总共插入行数
     *      'delete' => 总共删除行数
     *    ]
     * ]
     */
    public static function getCommit(string $path, string $hash, $type = 0)
    {
        chdir($path);
        $cmd = new Command(
            'git log %s -1 --format=%s %s',
            self::normalBranch($hash),
            'raw',
            $type == 1 ? '--numstat' : ''
        );
        $commit = [
            'parent' => [],
            'stats' => [],
            'tstats' => [
                'file' => 0,
                'insect' => 0,
                'delete' => 0
            ]
        ];
        $cmd->execute();
        $lineType = 0;
        foreach ($cmd->outputs as $line) {
            switch ($lineType) {
                case 0:
                    if ($line == '') {
                        $lineType = 1;
                        continue;
                    }
                    $arr = explode(' ', $line);
                    switch ($arr[0]) {
                        case 'parent':
                            $commit['parent'][] = $arr[1];
                            break;
                        case 'author':
                        case 'committer':
                            $commit[$arr[0]] = [
                                'name' => $arr[1],
                                'email' => trim($arr[2], '<>'),
                                'date' => self::getLocalTime($arr[3], $arr[4]),
                            ];
                            break;
                        default:
                            $commit[$arr[0]] = $arr[1];
                            break;
                    }
                    break;
                case 1:
                    if ($line == '') {
                        $lineType = 2;
                        $commit['msg'] = implode("\n", $commit['msg']);
                        continue;
                    }
                    $commit['msg'][] = ltrim($line);
                    break;
                case 2:
                    list($insect, $delete, $file) = preg_split('#\s+#', $line, 3);
                    $commit['stats'][] = array(
                        'file' => $file,
                        'insect' => $insect,
                        'delete' => $delete,
                    );
                    $commit['tstats']['file']++;
                    $commit['tstats']['insect'] += $insect;
                    $commit['tstats']['delete'] += $delete;
                    break;
            }
        }
        return $commit;
    }


    /**
     * 获取文件的变化
     * @param string $path
     * @param string $hash
     * @param string $file
     * @param null $fromHash
     * @return array
     */
    public static function lsDiffs(string $path, string $hash, string $file, $fromHash = null)
    {
        $commit = self::getCommit($path, $hash, 0);
        // 检查是否为第1个commit
        if (empty($commit['parent'])) {
            $cmd = new Command('git ls-tree %s %s', self::normalBranch($hash), $file);
            if (!$cmd->execute() && !empty($cmd->outputs)) {
                $line = $cmd->outputs[0];
                $arr = preg_split('#\s+#', $line, 4);
                $blobHash = $arr[2];
                $cmd = new Command('git cat-file -p %s', $blobHash);
                $cmd->execute();
                $blocks = [];
                foreach ($cmd->outputs as $key => $line) {
                    $number = $key + 1;
                    $blocks[] = [
                        'from' => 0,
                        'to' => $number,
                        'line' => $line,
                        'type' => 'insect',
                    ];
                }
                return $blocks;
            }
            return false;
        }

        // 加入有对比
        $cmd = new Command(
            'git diff %s..%s -- %s',
            $fromHash === null ? $commit['parent'][0] : $hash,
            $hash,
            $file
        );

        $cmd->execute();

        $blocks = [];
        $fromLine = 0;
        $toLine = 0;

        for ($i = 0, $l = count($cmd->outputs); $i < $l; $i++) {
            $line = $cmd->outputs[$i];
            if (strpos($line, 'diff --git ') === 0) {
                // 查找下一行
                $i++;
                $line = $cmd->outputs[$i];
                if (strncmp($line, 'index', 5) !== 0) {
                    $i++;
                    $line = $cmd->outputs[$i];
                }
                $lastLine = $line;
                // 跨越两行
                $i++;
                //可能是已结是文件的结尾  跨越两行未定义跨一行！！
                $line = $cmd->outputs[$i];
                if ($line === false) {
                    $line = $lastLine;
                }
                if (strncmp($line, 'Binary', 6) === 0) {
                    $diff['type'] = 'bin';
                } else {
                    $diff['type'] = 'file';
                    $i++;
                }
            } else {
                if (strpos($line, '@@ -') === 0) {
                    $arr = explode(' ', $line, 5);
                    list($fromLine) = explode(',', substr($arr[1], 1));
                    list($toLine) = explode(',', substr($arr[2], 1));
                    $fromLine--;
                    $toLine--;
                    $blocks[] = [
                        'from' => $fromLine,
                        'to' => $toLine,
                        'line' => $line,
                        'type' => 'start',
                    ];
                } else {
                    if ($line) {
                        switch ($line[0]) {
                            case '-': // 表示是起始commit的文件
                                $fromLine++;
                                $blocks[] = array(
                                    'from' => $fromLine,
                                    'line' => $line,
                                    'type' => 'delete',
                                );
                                break;
                            case '+':
                                $toLine++;
                                $blocks[] = array(
                                    'to' => $toLine,
                                    'line' => $line,
                                    'type' => 'insect',
                                );
                                break;
                            case '\\':
                                $blocks[] = array(
                                    'line' => $line,
                                    'type' => 'end',
                                );
                                break;
                            default:
                                $fromLine++;
                                $toLine++;

                                $blocks[] = array(
                                    'from' => $fromLine,
                                    'to' => $toLine,
                                    'line' => $line,
                                    'type' => 'both',
                                );
                        }
                    } else {
                        $fromLine++;
                        $toLine++;
                        $blocks[] = array(
                            'from' => $fromLine,
                            'to' => $toLine,
                            'line' => $line,
                            'type' => 'both',
                        );
                    }
                }
            }
        }
        return $blocks;
    }

    /**
     * 获取本地时间戳
     * @param $stamp
     * @param $tz
     * @param null $curTz
     * @return int
     */
    private static function getLocalTime($stamp, $tz, $curTz = null)
    {
        $tz = preg_replace('#^(\-?)(0)?(\d{1,2})00$#', '\1\3', $tz);
        if ($curTz === null) {
            $curTz = date('Z');
        }
        return $stamp + ($curTz - $tz * 3600);
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
        $len = strlen("refs/{$type}/");
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
