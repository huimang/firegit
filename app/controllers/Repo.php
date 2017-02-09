<?php
use huimang\git\Repository;

/**
 *
 * @author: ronnie
 * @since: 2017/1/15 17:17
 * @copyright: 2017@firegit.com
 * @filesource: Repo.php
 */
class RepoController extends BaseController
{
    private $repo;
    private $repoPath;
    private $branch;
    private $file;

    /**
     * 载入repo信息
     */
    public function init()
    {
        parent::init();

        $repo = new RepoModel();
        $repo = $repo->getRepo($_GET['repo_id']);
        if (!$repo) {
            throw new \huimang\Exception('notfound');
        }
        $this->repo = $repo;
        $this->repoPath = sprintf('%s/%s/%s.git', REPO_PATH, $repo['group'], $repo['name']);

        $uri = $this->_request->getRequestUri();
        $uri = substr($uri, 6); // 去掉/tree/
        $uri = preg_replace('#^[a-z]+/#', '', $uri);
        if ($uri) {
            if (preg_match('#^([a-z0-9]{40})(?:\/(.*))?$#', $uri, $ms)) {
                $this->branch = $ms[1];
                $this->file = $ms[2] ?? '';
            } else {
                // 算出分支和file
                $branches = array_keys(Repository::lsBranches($this->repoPath));
                $found = false;
                for ($i = count($branches) - 1; $i >= 0; $i--) {
                    $_branch = $branches[$i];
                    if (strpos($uri, $_branch . '/') === 0) {
                        $found = true;
                        $this->branch = $_branch;
                        $this->file = rtrim(substr($uri, strlen($_branch) + 1), '/');
                        break;
                    }
                }
                if (!$found) {
                    throw new \huimang\Exception('notfound');
                }
            }
        } else {
            $this->branch = 'master';
            $this->file = '';
        }

        $this->_view->repo = $repo;
        $this->_view->branch = $this->branch;
        $this->_view->repoNav = $this->_request->action;
    }


    public function indexAction()
    {
        $files = \huimang\git\Repository::lsTree($this->repoPath, $this->branch);

        $this->packFiles($files['file']);
        $branches = Repository::lsBranches($this->repoPath);
        $this->_view->branches = $branches;
        $this->_view->files = $files;
        $this->_view->repoNav = 'code';
        $this->_view->showSummary = true;
        $this->_view->branchNav = 'tree';
    }

    /**
     * tree
     */
    public function treeAction()
    {
        $files = \huimang\git\Repository::lsTree($this->repoPath, $this->branch, $this->file);


        $this->packFiles($files['file']);
        $this->_view->files = $files;
        $ppath = dirname($this->file);
        if ($ppath != '/' && $ppath != '') {
            $ppath .= '/';
        }
        $branches = Repository::lsBranches($this->repoPath);
        $this->_view->branches = $branches;
        $this->_view->ppath = $ppath;
        $this->_view->dir = $this->file;
        $this->_view->repoNav = 'code';
        $this->_view->branchNav = 'tree';
    }

    private $fileCsses = [
        'code' => ['php', 'java', 'js', 'css', 'go', 'html', 'phtml', 'xml', 'json', 'c', 'h'],
        'text' => ['md', 'txt', 'gitignore', 'ini'],
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'],
        'video' => ['mp4', 'flv', 'wmv'],
        'sound' => ['mp3', 'wma'],
        'word' => ['doc', 'docx'],
        'excel' => ['xls', 'xlsx'],
        'powerpoint' => ['ppt', 'pptx'],
        'archive' => ['zip', 'gz', 'tar.gz', '7zip', 'rar']
    ];

    private function packFiles(&$files)
    {
        foreach ($files as &$file) {
            foreach ($this->fileCsses as $css => $exts) {
                if (in_array($file['ext'], $exts)) {
                    $file['css'] = "fa fa-file-{$css}-o";
                    break;
                }
            }
            if (!isset($file['css'])) {
                $file['css'] = 'fa fa-file-o';
            }

            $file['_size'] = $file['size'];
            if ($file['size'] > 512 * 1024) {
                $file['size'] = sprintf('%.2fM', $file['size'] / (1024 * 1024));
            } elseif ($file['size'] > 1024) {
                $file['size'] = sprintf('%.2fk', $file['size'] / 1024);
            } else {
                $file['size'] .= 'b';
            }
        }
    }


    private $fileLangs = [
        'self' => ['js', 'css', 'xml', 'go'],
        'php' => ['php', 'phtml'],
        'html' => ['html']
    ];

    /**
     * 显示文章
     * @throws \huimang\Exception
     */
    public function blobAction()
    {
        $content = \huimang\git\Repository::catFile($this->repoPath, $this->branch, $this->file);

        $ppath = dirname($this->file);
        if ($ppath != '/' && $ppath != '') {
            $ppath .= '/';
        }

        $ext = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));
        $language = $ext;
        foreach ($this->fileLangs as $lang => $exts) {
            if (in_array($ext, $exts)) {
                if ($lang == 'self') {
                    break;
                } else {
                    $language = $lang;
                }
                break;
            }
        }

        $this->_view->ppath = $ppath;
        $this->_view->file = $this->file;
        $this->_view->content = $content;
        $this->_view->language = $language;
        $this->_view->repoNav = 'code';
    }


    /**
     * 项目的提交
     */
    public function commitsAction()
    {
        $datas = Repository::lsCommits($this->repoPath, $this->branch, 60);
        $this->_view->commits = $datas['commits'];
        $this->_view->next = $datas['next'];

        $branches = Repository::lsBranches($this->repoPath);
        $this->_view->branches = $branches;
        $this->_view->branchNav = 'commits';
    }

    /**
     * 单个commit
     */
    public function commitAction()
    {
        $hash = $this->_request->getParam('hash');

        $commit = Repository::getCommit($this->repoPath, $this->branch, 1);
        $this->_view->commit = $commit;

        // 获取所在分支
        $branches = Repository::lsBranchesByCommit($this->repoPath, $this->branch);
        $this->_view->branches = $branches;
        $this->_view->repoNav = 'commits';
    }

    public function diffAction()
    {
        $diffs = Repository::lsDiffs($this->repoPath, $this->branch, $this->file);
        $this->_view->diffs = $diffs;
    }

    public function branchesAction()
    {
        $branches = Repository::lsBranches($this->repoPath);
        $this->_view->branches = $branches;
    }

    public function tagsAction()
    {
        $tags = Repository::lsTags($this->repoPath);
        $this->_view->tags = $tags;
    }
}
