<?php

/**
 *
 * @author: ronnie
 * @since: 2017/1/13 14:37
 * @copyright: 2017@hunbasha.com
 * @filesource: Index.php
 */
class IndexController extends BaseController
{
    public function indexAction()
    {
        $this->_layout->title = '首页';
        $rModel = new RepoModel();
        $repos = $rModel->getUserCreateRepos($this->userId, 5);
        $this->_view->repos = $repos;

        $brepos = $rModel->getUserBeloneRepos($this->userId, 5);
        $this->_view->brepos = $brepos;
    }
}
