<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Branch;
use App\Models\OnlineOrderItem;

class OnlineOrderHistory extends JsonResource
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
              'pos_user_id' => $this->pos_user_id,
              'branch_id' => $this->branch_id,
              'theBranch' => Branch::where('id', $this->branch_id)->first(),

              'branch_name' => $this->branch_name,
              'token' =>  json_decode($this->token),

              'order_bill' => $this->order_bill,
              'vat' => $this->vat,
              'vat_system' => $this->vat_system,
              'cgst' => $this->cgst,
              'sgst' => $this->sgst,

              'total_payable' => $this->total_payable,
              'bill_distribution' => json_decode($this->bill_distribution),

              'payment_method' => $this->payment_method,
              'note_to_rider' => $this->note_to_rider,
              'note_to_rider' => $this->note_to_rider,
              'delivery_phn_no' => $this->delivery_phn_no,
              'delivery_address' => $this->delivery_address,
              'time_to_deliver' => $this->time_to_deliver,
              'reason_of_cancel' => $this->reason_of_cancel,

              'delivery_boy_name' => $this->delivery_boy_name,
              'delivery_boy_id' => $this->delivery_boy_id,
              'delivery_status' => $this->delivery_status,
              'created_at' => $this->created_at,

              'is_accepted' => $this->is_accepted,
              'is_cancelled' => $this->is_cancelled,
              'is_delivered' => $this->is_delivered,
              'orderedItems' => OnlineOrderItem::where('order_group_id', $this->id)->get(),
          ];
    }
}
