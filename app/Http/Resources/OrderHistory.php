<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Branch;
use App\Models\OrderItem;

class OrderHistory extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'work_period_id' => $this->work_period_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'branch_id' => $this->branch_id,
            'theBranch' => Branch::where('id', $this->branch_id)->first(),

            'branch_name' => $this->branch_name,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'table_id' => $this->table_id,
            'table_name' => $this->table_name,

            'waiter_id' => $this->waiter_id,
            'waiter_name' => $this->waiter_name,
            'dept_tag_id' => $this->dept_tag_id,
            'dept_tag_name' => $this->dept_tag_name,
            'token' =>  json_decode($this->token),

            'total_guest' => $this->total_guest,
            'service_charge' => $this->service_charge != null ? $this->service_charge: 0 ,
            'discount' => $this->discount!== null ?$this->discount: 0,
            'order_bill' => $this->order_bill,
            'vat' => $this->vat,
            'vat_system' => $this->vat_system,
            'cgst' => $this->cgst,
            'sgst' => $this->sgst,
            'dept_commission' => $this->dept_commission,


            'total_payable' => $this->total_payable,
            'bill_distribution' => json_decode($this->bill_distribution),
            'paid_amount' => $this->paid_amount,
            'return_amount' => $this->return_amount,
            'is_paid' => $this->is_paid,

            'is_accepted' => $this->is_accepted,
            'is_cancelled' => $this->is_cancelled,
            'is_settled' => $this->is_settled,
            'is_ready' => $this->is_ready,
            'created_at' => $this->created_at,

            'orderedItems' => OrderItem::where('order_group_id', $this->id)->get(),
        ];
    }
}
