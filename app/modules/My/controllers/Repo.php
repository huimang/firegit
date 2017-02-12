<?php

/**
 *
 * @author: ronnie
 * @since: 2017/2/12 09:27
 * @copyright: 2017@firegit.com
 * @filesource: Repo.php
 */
class RepoController extends MyController
{
    public function init()
    {
        parent::init();

        if ($this->_layout) {
            $this->_layout->mainNav = 'repo';
        }
        $this->_view->navType = $this->_request->action;
    }


    public function indexAction()
    {
        $this->_layout->title = 'GIT库>我创建的GIT库';
        $model = new RepoModel();
        $repos = $model->getUserCreateRepos($this->userId, $this->_size, $this->_page);
        $this->_view->repos = $repos['list'];

        $this->setPagination($repos['total']);
    }

    public function belongAction()
    {
        $this->_layout->title = 'GIT库>我拥有的GIT库';
        $model = new RepoModel();
        $repos = $model->getUserBeloneRepos($this->userId, $this->_size);
        foreach ($repos as &$repo) {
            $repo['role_name'] = $this->roles[$repo['role']];
        }
        $this->_view->repos = $repos;
    }

    /**
     * 添加仓库
     */
    public function addAction()
    {
        $this->_layout->title = 'GIT库>添加GIT库';
    }

    public function _addAction()
    {
        $model = new RepoModel();
        $repoId = $model->addRepo(
            $_POST['group'],
            $_POST['name'],
            $this->userId,
            $_POST['desc'],
            [
                'anonymouse' => (isset($_POST['anonymouse']) && $_POST['anonymouse'] == 1)
            ]);
        $this->setPostDatas([
            'repo_id' => $repoId,
            'group' => $_POST['group'],
            'name' => $_POST['name']
        ]);
    }

    public function _deleteAction()
    {
        $repoId = intval($_POST['repo_id']);
        $model = new RepoModel();
        $model->delRepo($repoId, $this->userId);
    }
}
