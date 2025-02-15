<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UpdateUserBalance implements ShouldQueue, ShouldBeUnique
{


    //执行队列
    // php artisan queue:work --daemon
    protected int $userId;
    protected float $amount;
    protected string $remark;



    public function __construct(int $userId, float $amount, string $remark ='')
    {
        $this->userId = $userId;
        $this->amount = $amount;
        $this->remark = $remark;
    }

    // 返回唯一标识符
    public function uniqueId(): string
    {
        return 'user_balance_' . $this->userId;
    }
    public function handle()
    {
        // 仅在金额不为0时执行
        if ($this->amount == 0) {
            return;
        }
        // 使用事务
        DB::transaction(function () {
            // 更新用户余额
            $user = User::findOrFail($this->userId);
            $user->balance += $this->amount;
            $user->save();
            // 写入资金操作日志
           FinancialLogModel::query()->create([
                'user_id' => $this->userId,
                'amount' => $this->amount,
                'type' => $this->amount > 0 ? 'add' : 'cut', // 根据金额判断是存入还是取出
                'remark' =>  $this->remark,
            ]);
        });
    }
}
