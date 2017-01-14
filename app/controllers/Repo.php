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
        $this->getView()->assign('repo', $repo);
    }

    public function commitAction()
    {

    }
}
