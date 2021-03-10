<?php

namespace Source\Models\Report;

use Source\Core\Model;
use Source\Core\Session;


/**
 * Class Online
 * @package Source\Models\Report
 */
class Online extends Model
{
    /**
     * @var int
     */
    private $sessionTime;

    /**
     * Online constructor.
     * @param int $sessionTime
     */
    public function __construct(int $sessionTime = 20)
    {
        $this->sessionTime = $sessionTime;
        parent::__construct("report_online", ["id"], ["ip", "url", "agent"]);
    }


    /**
     * @param bool $count
     * @return array|int|null
     */
    public function findByActive(bool $count = false)
    {
        $find = $this->find("updated_at >= NOW() - INTERVAL ($this->sessionTime) MINUTE");
        if($count){
            return $find->count();
        }

        return $find->fetch(true);
    }


    /**
     * @param bool $clear
     * @return $this
     */
    public function report(bool $clear = true): Online
    {
        $session = new Session();

        //não existe sessão com o nome online entao cria uma
        if(!$session->has("online")) {
            $this->user = ($session->authUser ?? null);
            $this->url = (filter_input(INPUT_GET, "route", FILTER_SANITIZE_STRIPPED) ?? "/");
            $this->ip = filter_input(INPUT_SERVER, "REMOTE_ADDR");
            $this->agent = filter_input(INPUT_SERVER, "HTTP_USER_AGENT");

            $this->save();
            $session->set("online", $this->id);
            return $this;
        }

        $find = $this->findById($session->online);

        //existe uma sessao com o nome online mas sem conteudo
        if(!$find) {
            $session->unset("online");
            return $this;
        }

        //existe uma sessão com o nome online e já tem conteudo
        $find->user = ($session->authUser ?? null);;
        $find->url = (filter_input(INPUT_GET, "route", FILTER_SANITIZE_STRIPPED) ?? "/");
        $find->pages += 1;
        $find->save();

        if($clear) {
            $this->clear();
        }

        return $this;

    }


    /**
     *
     */
    public function clear(): void
    {
        $this->delete("updated_at < NOW() - INTERVAL {$this->sessionTime} MINUTE", "null");
    }


    /**
     * @return bool
     */
    public function save(): bool
    {
        /** Update Access */
        if(!empty($this->id)) {

            $onlineId = $this->id;
            $this->update($this->safe(), "id = :id", "id={$onlineId}");
            if($this->fail()) {
                $this->message->error("Erro ao atualizar, verifique os dados...");
                return false;
            }
        }

        /** Create Access */
        if(empty($this->id)) {

            $onlineId = $this->create($this->safe());

            if($this->fail()) {
                $this->message->error("Erro ao criar registo, tente novamente...");
                return false;
            }
        }

        $this->data = $this->findById($onlineId)->data();
        return true;
    }

}