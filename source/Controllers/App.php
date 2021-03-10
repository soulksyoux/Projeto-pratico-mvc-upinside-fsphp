<?php


namespace Source\Controllers;


use Source\Core\Controller;
use Source\Core\Session;
use Source\Models\Auth;
use Source\Support\Message;

class App extends Controller
{
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_APP . "/");

        if(!Auth::user()) {
            (new Message())->warning("Utilizador tem de estar logado para poder aceder a APP")->flash();
            redirect("/entrar");
        }

        (new Access())->report();
        (new Online())->report();
    }

    public function home() {
        echo flash();
        var_dump(Auth::user());
        echo "<a title='sair' href='" . url("/app/sair") . "'>Sair</a>";
    }

    public function logout() {
        (new Message())->info("User saiu com sucesso " . Auth::user()->first_name . " - Volte logo!!!")->flash();
        Auth::logout();
        redirect("/entrar");
    }

}