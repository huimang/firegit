<?php

/**
 *
 * @author: ronnie
 * @since: 2017/1/15 17:17
 * @copyright: 2017@firegit.com
 * @filesource: Repo.php
 */
class RepoController extends \Yaf\Controller_Abstract
{
    public function indexAction()
    {
        $repo = new RepoModel();
        $repo = $repo->getRepo($_GET['repo_id']);
        if (!$repo) {
            throw new \huimang\Exception('notfound');
        }

        $branch = 'master';
        $path = sprintf('%s/%s/%s.git', REPO_PATH, $repo['group'], $repo['name']);
        $files = \huimang\git\Repository::lsTree($path, $branch);
        $this->_view->repo = $repo;
        $this->_view->files = $files;
        $this->_view->branch = $branch;
    }

    public function treeAction()
    {
        $repo = new RepoModel();
        $repo = $repo->getRepo($_GET['repo_id']);
        if (!$repo) {
            throw new \huimang\Exception('notfound');
        }

        $branch = rtrim($this->_request->getParam('branch'), '/');
        $dir = rtrim($this->_request->getParam('dir'), '/');

        $path = sprintf('%s/%s/%s.git', REPO_PATH, $repo['group'], $repo['name']);
        $files = \huimang\git\Repository::lsTree($path, $branch, $dir);
        $this->_view->repo = $repo;
        $this->_view->files = $files;
        $this->_view->branch = $branch;
    }

    public function blobAction()
    {
        
    }



    public function commitAction()
    {

    }
}
