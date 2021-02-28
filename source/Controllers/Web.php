<?php


namespace Source\Controllers;

use Source\Core\Connect;
use Source\Core\Controller;
use Source\Core\Session;
use Source\Models\Auth;
use Source\Models\Category;
use Source\Models\Faq\Channel;
use Source\Models\Faq\Question;
use Source\Models\Post;
use Source\Models\User;
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
            "video" => "lDZGl9Wdc7Y",
            "blog" => (new Post())
                ->find()
                ->order("post_at DESC")
                ->limit(6)
                ->fetch(true)
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
            "video" => "lDZGl9Wdc7Y",
            "faq" => (new Question())
                ->find("channel_id = :id", "id=1", "question, response")
                ->order("order_by")
                ->fetch(true)
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

        $blog = (new Post())->find();
        $pager = new Pager(url("/blog/page/"));
        $pager->pager($blog->count(), 9, ($data['page'] ?? 1));

        echo $this->view->render("blog", [
            "head" => $head,
            "title" => "Titulo do Blog",
            "blog" => $blog->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render()
        ]);
    }


    /**
     * SITE BLOG SEARCH
     * @param array $data
     */
    public function blogSearch(array $data): void
    {

        if(!empty($data["s"])) {
            $search = filter_var($data['s'], FILTER_SANITIZE_STRIPPED);
            echo json_encode(["redirect" => url("/blog/buscar/{$search}/1")]);
            return;
        }

        if(empty($data['terms'])) {
            redirect("/blog");
        }

        $search = filter_var($data['terms'], FILTER_SANITIZE_STRIPPED);
        $page = (filter_var($data['page'], FILTER_VALIDATE_INT) >= 1 ? $data['page'] : 1);

        $head = $this->seo->render(
            "Pesquisa por {$search} - " . CONF_SITE_NAME,
            "Confira os resultados de sua pesquisa para {$search}",
            url("/blog/buscar/{$search}/{$page}"),
            theme("/assets/images/share.jpg")
        );

        $blogSearch = (new Post())->find("title LIKE :s OR subtitle LIKE :s", "s=%{$search}%");

        if(!$blogSearch->count()){
            echo $this->view->render("blog", [
               "head" => $head,
                "title" => "Pesquisa por:",
                "search" => $search,
            ]);
            return;
        }

        $pager = new Pager(url("/blog/buscar/{$search}/"));
        $pager->pager($blogSearch->count(), 6, $page);

        echo $this->view->render("blog", [
            "head" => $head,
            "title" => "Pesquisa por:",
            "search" => $search,
            "blog" => $blogSearch->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render()
        ]);

    }

    /**
     * SITE BLOG POST
     * @param array $data
     */
    public function blogPost(array $data): void {

        $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
        $post = (new Post())->findByUri($data['uri']);
        if(!$post) {
            redirect("/404");
        }

        $post->views += 1;
        $post->save();

        $head = $this->seo->render(
            "{$post->title} - " . CONF_SITE_NAME,
            $post->subtitle,
            url("/blog/{$post->uri}"),
            image($post->cover, 1200, 628)
        );


        $blog = (new Post())
            ->find("category = :c AND id != :i", "c={$post->category}&i={$post->id}")
            ->order("rand()")
            ->limit(3)
            ->fetch(true);

        echo $this->view->render("blog-post", [
            "head" => $head,
            "post" => $post,
            "related" => $blog
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

    /**
     *
     */
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

    /**
     * SITE REGISTER
     * @param null|array $data
     */
    public function register(?array $data): void
    {
        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            if (in_array("", $data)) {
                $json['message'] = $this->message->info("Informe seus dados para criar sua conta.")->render();
                echo json_encode($json);
                return;
            }

            $auth = new Auth();
            $user = new User();
            $user->bootstrap(
                $data["first_name"],
                $data["last_name"],
                $data["email"],
                $data["password"]
            );

            if ($auth->register($user)) {
                $json['redirect'] = url("/confirma");
            } else {
                $json['message'] = $auth->message()->render();
            }

            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
            "Criar Conta - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/cadastrar"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("auth-register", [
            "head" => $head
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

        $data = new \stdClass();
        $data->title = "Confirme seu cadastro.";
        $data->image = theme("/assets/images/optin-confirm.jpg");
        $data->desc = "Enviamos um link de confirmação para seu e-mail. Acesse e siga as instruções para concluir seu cadastro e comece a controlar com o CaféControl";

        echo $this->view->render("optin", [
            "head" => $head,
            "data" => $data
        ]);
    }

    /**
     * @param array $data
     *
     */
    public function success($data): void
    {
        $email = base64_decode($data["email"]);
        $user = (new User())->findByEmail($email);
        if($user && $user->status != "confirmed") {
            $user->status = "confirmed";
            $user->save();
        }

        $data = new \stdClass();
        $data->title = "Tudo pronto. Você já pode controlar :)";
        $data->image = theme("/assets/images/optin-success.jpg");
        $data->desc = "Bem-vindo(a) ao seu controle de contas, vamos tomar um café?";
        $data->link = url("/entrar");
        $data->linkTitle = "Fazer Login";

        $head = $this->seo->render(
            "Bem-vindo(a) ao - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/obrigado"),
            theme("/assets/images/share.jpg"),
        );

        echo $this->view->render("optin", [
            "head" => $head,
            "data" => $data
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

