<?php


namespace Source\Models\CafeApp;


use Source\Core\Model;
use Source\Models\User;

/**
 * Class AppInvoice
 * @package Source\Models\CafeApp
 */
class AppInvoice extends Model
{
    /**
     * AppInvoice constructor.
     */
    public function __construct()
    {
        parent::__construct("app_invoices", ["id"],
            ["user_id", "wallet_id", "category_id", "description", "type", "value", "due_at", "repeat_when"]
        );
    }


    /**
     * @param User $user
     * @param int $afterMonth
     */
    public function fixed(User $user, int $afterMonth = 3): void
    {
        $fixed = $this->find("user_id = :user AND status = 'paid' AND type IN ('fixed_income','fixed_expense')",
        "user={$user->id}")->fetch(true);

        if(empty($fixed)) {
            return;
        }

        foreach ($fixed as $fixedItem) {
            $invoice = $fixedItem->id;
            $start = new \DateTime($fixedItem->due_at);
            $end = new \DateTime("+{$afterMonth}month");

            if($fixedItem->period == "month") {
                $interval = new \DateInterval("P1M");
            }

            if($fixedItem->period == "year") {
                $interval = new \DateInterval("P1Y");
            }

            $period = new \DatePeriod($start, $interval, $end);

            foreach ($period as $item) {

                //valida se já existe alguma linha no invoice relativa à fixedItem e as datas definidas no period
                $getFixed = $this->find(
                    "user_id = :user_id AND 
                    invoice_of = :invoice_of AND 
                    year(due_at) = :due_year AND 
                    month(due_at) = :due_month",
                    "user_id={$user->id}&
                    invoice_of={$fixedItem->id}&
                    due_year={$item->format("Y")}&
                    due_month={$item->format("m")}",
                    "id")
                    ->fetch();

                if(empty($getFixed)) {
                    //cria registo de linha no invoice
                    $newItem = $fixedItem;
                    $newItem->id = null;
                    $newItem->invoice_of = $invoice;
                    $newItem->type = str_replace("fixed_", "", $fixedItem->type);
                    $newItem->due_at = $item->format("Y-m-d");
                    $newItem->status = ($item->format("Y-m-d") <= date ("Y-m-d") ? "paid" : "unpaid");
                    $newItem->save();
                }

            }


        }

        //var_dump($fixed);
    }

    /**
     * @param User $user
     * @param string $type
     * @param array|null $filter
     * @param int|null $limit
     * @return array|null
     */
    public function filter(User $user, string $type, ?array $filter, ?int $limit = null): ?array
    {
        $status = (!empty($filter["status"]) && $filter["status"] == "paid" ? "AND status = 'paid" :
            (!empty($filter["status"]) && $filter["status"] == "unpaid" ? "AND status = 'unpaid" : null));

        $category = (!empty($filter["catogory"]) && $filter["category"] != "all" ? "AND category = '{$filter["category"]}'" : null);

        $due_year = (!empty($filter["date"]) ? explode("-", $filter["date"])[1] : date("Y"));
        $due_month = (!empty($filter["date"]) ? explode("-", $filter["date"])[0] : date("m"));

        $due_at = "AND year(due_at) = '{$due_year}' AND month(due_at) = '{$due_month}'";

        $due = $this
            ->find("user_id = :user AND type = :type {$status} {$category} {$due_at}", "user={$user->id}&type={$type}")
            ->order("day(due_at) ASC");

        if($limit) {
            $due->limit($limit);
        }

        return $due->fetch(true);
    }

    /**
     * @return AppCategory
     */
    public function category(): AppCategory {
        $category = (new AppCategory())->findById($this->category_id);
        return $category;
    }
}