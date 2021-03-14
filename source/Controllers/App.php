<?php


namespace Source\Controllers;


use Source\Core\Controller;
use Source\Core\Session;
use Source\Models\Auth;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Support\Message;

class App extends Controller
{
    /** @var User */
    private $user;

    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_APP . "/");

        if(!$this->user = Auth::user()) {
            (new Message())->warning("Utilizador tem de estar logado para poder aceder a APP")->flash();
            redirect("/entrar");
        }

        (new Access())->report();
        (new Online())->report();
    }

    public function home() {
        echo $this->view->render("home", [

        ]);
    }

    public function logout() {
        (new Message())->info("User saiu com sucesso " . Auth::user()->first_name . " - Volte logo!!!")->flash();
        Auth::logout();
        redirect("/entrar");
    }

}