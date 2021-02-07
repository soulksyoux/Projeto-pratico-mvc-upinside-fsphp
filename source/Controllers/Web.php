<?php


namespace Source\Controllers;

use Source\Core\Controller;

/**
 * Class Web
 * @package Source\Controllers
 */
class Web extends Controller {

    /**
     * Web constructor.
     */
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_THEME . "/");
    }

    /**
     * SITE HOME
     */
    public function home(): void {
        echo $this->view->render("home", [
           "title" => "CafeControl - Faça a gestão de suas contas com o melhor café!"
        ]);
    }

    /**
     * SITE ABOUT
     */
    public function about(): void {
        echo "About";
    }

    /**
     * SITE NAV ERROR
     * @param array $data
     */
    public function error(array $data): void {
        echo $this->view->render("error", [
           "title" => $data['errcode'] . " | Oppps"
        ]);
    }
}

