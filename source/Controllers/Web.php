<?php


namespace Source\Controllers;

use Source\Core\Connect;
use Source\Core\Controller;
use Source\Support\Pager;

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
        //redirect("/ops/manutencao");
        //Connect::getInstance();
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_THEME . "/");
    }

    /**
     * SITE HOME
     */
    public function home(): void {
        $head = $this->seo->render(
            CONF_SITE_NAME . " - " . CONF_SITE_TITLE,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("home", [
            "head" => $head,
            "video" => "lDZGl9Wdc7Y"
        ]);
    }

    /**
     * SITE ABOUT
     */
    public function about(): void {
        $head = $this->seo->render(
            CONF_SITE_NAME . " - " . CONF_SITE_TITLE,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("about", [
           "head" => $head,
            "video" => "lDZGl9Wdc7Y"
        ]);
    }

    /**
     * SITE BLOG
     */
    public function blog(?array $data): void {
        $head = $this->seo->render(
            "Blog - " . CONF_SITE_NAME,
            "Navegue pelo nosso blog e tire dicas interessantes para o seu dia a dia",
            url("/blog"),
            theme("/assets/images/share.jpg")
        );

        $pager = new Pager(url("/blog/page/"));
        $pager->pager(100, 10, ($data['page'] ?? 1));


        echo $this->view->render("blog", [
            "head" => $head,
            "paginator" => $pager->render()
        ]);
    }

    /**
     * SITE BLOG POST
     * @param array $data
     */
    public function blogPost(array $data): void {
        $postName = $data['postName'];

        $head = $this->seo->render(
            "POST NAME - " . CONF_SITE_NAME,
            "POST HEADLINE",
            url("/blog/{postName}"),
            theme("/assets/images/share.jpg")
        );

        $data = new \stdClass();

        echo $this->view->render("blog-post", [
            "head" => $head,
            "postName" => $postName,
            "data" => $this->seo->data()
        ]);
    }

    /**
     * SITE AUTH
     */
    public function login(): void
    {
        $head = $this->seo->render(
            "Entrar - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/entrar"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("auth-login", [
            "head" => $head,
        ]);
    }

    public function forget(): void
    {
        $head = $this->seo->render(
            "Recuperar Password - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/recuperar"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("auth-forget", [
            "head" => $head,
        ]);
    }

    public function register(): void
    {
        $head = $this->seo->render(
            "Registar - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/cadastrar"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("auth-register", [
            "head" => $head,
        ]);
    }
    
    /**
     * SITE OPTIN
     */
    public function confirm(): void
    {
        $head = $this->seo->render(
            "Confirma seu Registo - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/confirma"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("optin-confirm", [
            "head" => $head,
        ]);
    }

    public function success(): void
    {
        $head = $this->seo->render(
            "Bem-vindo(a) ao - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/obrigado"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("optin-success", [
            "head" => $head,
        ]);
    }

    /**
     * SITE TERMS
     */
    public function terms(): void {
        $head = $this->seo->render(
            CONF_SITE_NAME . " - Termos de uso",
            CONF_SITE_DESC,
            url("/terms"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("terms", [
            "head" => $head,
        ]);
    }



    /**
     * SITE NAV ERROR
     * @param array $data
     */
    public function error(array $data): void {
        $error = new \stdClass();

        switch ($data['errcode']) {
            case "problems":
                $error->code = "OPS";
                $error->title = "Estamos a ter alguns problemas...";
                $error->message = "Lamentamos, mas o nosso serviço não está disponivel neste momento, pf tente mais tarde";
                $error->linkTitle = "Enviar Email";
                $error->link = url_back("mailto:" . CONF_MAIL_SUPPORT);
                break;
            case "manutencao":
                $error->code = "OPS";
                $error->title = "Lamentamos, mas estamos em manutenção";
                $error->message = "De momento o nosso site esta em manutenção, volte mais tarde pf";
                $error->linkTitle = null;
                $error->link = null;
                break;
            default:
                $error->code = $data['errcode'];
                $error->title = "Oppps. Conteudo indisponivel :(";
                $error->message = "Lamentamos, mas o conteudo que esta a tentar aceder de momento esta indisponivel";
                $error->linkTitle = "Continue a navegar";
                $error->link = url_back();
                break;
        }

        $head = $this->seo->render(
            "{$error->code} | {$error->title}",
            $error->message,
            url("/ops/{$error->code}"),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("error", [
           "head" => $head,
            "error" => $error
        ]);
    }
}

