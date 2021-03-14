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
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\User;
use Source\Support\Email;
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

        /**
        $email = new Email();
        $email->bootstrap(
            "Teste de fila de email " .time(),
            "Este é apenas um teste de envio de email",
            "andytod80@gmail.com",
            "Andy Garcia"
        )->sendQueue();
        */

        (new Access())->report();
        (new Online())->report();

        //$online = new Online();
        //var_dump($online->findByActive(true), $online->findByActive());

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

        $blogSearch = (new Post())->find("MATCH(title, subtitle) AGAINST(:s)", "s={$search}");

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
     * SITE BLOG CATEGORY SEARCH
     * @param array $data
     */
    public function blogCategory(array $data): void
    {
        $categoryUri = filter_var($data["category"], FILTER_SANITIZE_STRIPPED);
        $category = (new Category())->findByUri($categoryUri);
        if(!$category) {
            redirect("/blog");
        }

        $blogCategory = (new Post())->find("category = :c", "c={$category->id}");


        $page = (!empty($data["page"]) && filter_var($data["page"], FILTER_VALIDATE_INT) >= 1 ? $data["page"] : 1);
        $pager = new Pager(url("/blog/categoria/{$category->uri}/"));
        $pager->pager($blogCategory->count(), 9, $page);

        $head = $this->seo->render(
            "Artigos em {$category->title} - " . CONF_SITE_NAME,
            $category->description,
            url("/blog/categoria/{$category->uri}/{$page}"),
            ($category->cover ? image($category->cover, 1200, 628) : theme("/assets/images/share.jpg"))
        );

        echo $this->view->render("blog", [
            "head" => $head,
            "title" => "Artigos em {$category->title}",
            "desc" => $category->description,
            "blog" => $blogCategory
                ->limit($pager->limit())
                ->offset($pager->offset())
                ->order("post_at DESC")
                ->fetch(true),
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


    public function login(?array $data): void
    {
        //se for true sifnifica que vem um post e que o user submeteu o form de login
        if(!empty($data["csrf"])) {


            if(!csrf_verify($data)) {
                $json["message"] = $this->message->error("Erro ao enviar, por favor use o formulário.")->render();
                echo json_encode($json);
                return;
            }

            if(request_limit("weblogintest", 3, 60 )) {

                $json["message"] = $this->message->error("Excedeu o numero de tentativas de login... Aguarde 60 sec.")->render();
                echo json_encode($json);
                return;
            }

            if(empty($data["email"]) || empty($data["password"])) {
                $json["message"] = $this->message->warning("Informe o seu email e senha por favor.")->render();
                echo json_encode($json);
                return;
            }

            $save = (!empty($data["save"]) ? true : false);
            $auth = new Auth();
            $login = $auth->login($data["email"], $data["password"], $save);

            if($login) {
                $json["redirect"] = url("/app");
            }else{
                $json["message"] = $auth->message()->before("Ops! ")->render();
            }

            echo json_encode($json);
            return;

        }

        $head = $this->seo->render(
            "Entrar - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/entrar"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("auth-login", [
            "head" => $head,
            "cookie" => filter_input(INPUT_COOKIE, "authEmail")
        ]);
    }

    /**
     * SITE PASSWORD FORGET
     * @param null|array $data
     */
    public function forget(array $data): void
    {
        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            if(empty($data["email"])) {
                $json["message"] = $this->message->warning("Informe o seu email para continuar...")->render();
                echo json_encode($json);
                return;
            }

            if(request_repeat("webforget", $data["email"])) {
                $json["message"] = $this->message->error("Voce já tentou este email antes...")->render();
                echo json_encode($json);
                return;
            }

            $auth = new Auth();
            if($auth->forget($data["email"])) {
                $json["message"] = $this->message->success("Acesse o seu email para completar...")->render();
            }else{
                $json["message"] = $auth->message()->render();
            }


            echo json_encode($json);
            return;
        }

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
     * SITE FORGET RESET
     * @param array $data
     */
    public function reset(array $data): void {

        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            $email = explode("|" , $data["code"])[0];
            $code = explode("|" , $data["code"])[1];

            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            if(!is_email($email)) {
                $json['message'] = $this->message->warning("Email invalido")->render();
                return;
            }

            if(empty($data["password"]) || empty($data["password_re"])){
                $json['message'] = $this->message->warning("Preencha as passwords")->render();
                echo json_encode($json);
                return;
            }

            $auth = new Auth();
            if($auth->reset($email, $code, $data["password"], $data["password_re"])) {
                $json['message'] = $this->message->success("Password redefinida com sucesso")->flash();
                $json["redirect"] = url("/entrar");
            }else{
                $json['message'] = $auth->message()->render();
            }


            echo json_encode($json);
            return;
        }


        $head = $this->seo->render(
          "Reset de password - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/recuperar"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("auth-reset", [
            "head" => $head,
            "code" => $data["code"],

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

        if(!is_email($email)) {
            $this->message->info("Código informado não é valido!")->flash();
            redirect("/cadastrar");
        }

        $user = (new User())->findByEmail($email);

        if (!$user || $email != $user->email) {
            $this->message->info("Cadastre-se para ativar a sua conta :)")->flash();
            redirect("/cadastrar");
        }

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

