<?php


namespace Source\Models;


use Source\Core\Model;
use Source\Core\Session;
use Source\Core\View;
use Source\Support\Email;
use Source\Support\Message;

/**
 * Class Auth
 * @package Source\Models
 */
class Auth extends Model
{
    /**
     * Auth constructor.
     */
    public function __construct()
    {
        parent::__construct("users", ["id"], ["email", "password"]);
    }


    /**
     * @return User|null
     */
    public static function user(): ?User{
        $session = new Session();
        if(empty($session->authUser)) {
            return null;
        }

        $user = (new User())->findById($session->authUser);
        return $user;
    }

    /**
     *
     */
    public static function logout(): void {
        $session = new Session();
        $session->unset("authUser");

    }


    /**
     * @param User $user
     * @return bool
     */
    public function register(User $user): bool
    {

        if (!$user->save()) {
            $this->message = $user->message;
            return false;
        }

        $view = new View(__DIR__ . "/../../shared/views/email");
        $message = $view->render("confirm", [
            "first_name" => $user->first_name,
            "confirm_link" => url("/obrigado/" . base64_encode($user->email))
        ]);

        (new Email())->bootstrap(
            "Ative sua conta no " . CONF_SITE_NAME,
            $message,
            $user->email,
            "{$user->first_name} {$user->last_name}"
        )->send();

        return true;
    }


    /**
     * @param string $email
     * @param string $password
     * @param bool $save
     * @return bool
     */
    public function login(string $email, string $password, bool $save = false): bool
    {
        if(!is_email($email)){
            $this->message->warning("O email informado não é válido.");
            return false;
        }

        if($save) {
            setcookie("authEmail", $email, time() + (60*60*24*7), "/");
        }else{
            setcookie("authEmail", null, time() - 3600, "/");
        }

        if(!is_passwd($password)){
            $this->message->warning("A password informada não é válida.");
            return false;
        }

        $user = (new User())->findByEmail($email);
        if(!$user) {
            $this->message->error("User inválido");
            return false;
        }

        if(!passwd_verify($password, $user->password)) {
            $this->message->error("Password inválido");
            return false;
        }

        if(passwd_rehash($user->password)) {
            $user->password = $password;
            $user->save();
        }

        (new Session())->set("authUser", $user->id);
        $this->message->success("User logado com sucesso!!!")->flash();


        return true;
    }


    /**
     * @param string $email
     * @return bool
     */
    public function forget(string $email): bool {
        $user = (new User())->findByEmail($email);

        if(empty($user)){
            $this->message()->warning("Email nao registado");
            return false;
        }

        $user->forget = md5(uniqid(rand(), true));
        $user->save();

        $view = new View(__DIR__ . "/../../shared/views/email");
        $message = $view->render("forget", [
            "first_name" => $user->first_name,
            "forget_link" => url("/recuperar/{$user->email}|{$user->forget}")
        ]);

        (new Email())->bootstrap(
            "Recuperar senha no " . CONF_SITE_NAME,
            $message,
            $user->email,
            "{$user->first_name} {$user->last_name}"
        )->send();

        return true;

    }


    /**
     * @param string $email
     * @param string $code
     * @param string $password
     * @param string $passwordRe
     * @return bool
     */
    public function reset(string $email, string $code, string $password, string $passwordRe): bool
    {

        $user = (new User())->findByEmail($email);
        if(!$user) {
            $this->message->warning("Conta para recuperacao nao encontrada.");
            return false;
        }

        if($user->forget != $code) {
            $this->message->error("Codigo de verificação invalido");
            return false;
        }

        if(!is_passwd($password)) {
            $min = CONF_PASSWD_MIN_LEN;
            $max = CONF_PASSWD_MAX_LEN;
            $this->message->warning("Password tem de ter mais de {$min} e menos de {$max} caracteres");
            return false;
        }

        if($password != $passwordRe) {
            $this->message->warning("Password de confirmaçao é invalida");
            return false;
        }

        $user->password = $password;
        $user->forget = null;
        $user->save();

        return true;


    }

}