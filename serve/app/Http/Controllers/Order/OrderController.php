<?php

namespace App\Http\Controllers\Order;

use App\Events\Order\OrderCancelEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderStoreRequest;
use App\Http\Resources\Order\OrderCollection;
use App\Http\Resources\Order\OrderDetail;
use App\Services\Order\OrderStore;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = auth('customers')->user()->orders();
        $orders = $orders->paginate($request->get('pageSize'));
        return $this->jsonSuccessResponse(new OrderCollection($orders));
    }

    public function show($order)
    {
        $order = auth('customers')->user()->orders()->where('no',$order)->first();
        if(!$order) return $this->jsonErrorResponse(404,"未找到此订单");
        return $this->jsonSuccessResponse(new OrderDetail($order));
    }

    public function store(OrderStoreRequest $request)
    {
        $customer = auth('customers')->user();
        OrderStore::store($customer,$request->get('address'),$request->get('items'),$request->get('remark'));
        $variant_ids = collect($request->get('items'))->pluck('variant_id');
        $customer->cartItems()->whereIn("variant_id",$variant_ids)->delete();
        return $this->jsonSuccessResponse();
    }

    public function update($order,Request $request)
    {
        $order = auth('customers')->user()->orders()->where('no',$order)->first();
        if(!$order) return $this->jsonErrorResponse(404,"未找到此订单");
        if($request->has("status")){
            switch($request->get('status')){
                case "cancel":
                    event(new OrderCancelEvent($order));
                    break;
                default:
                    return $this->jsonErrorResponse(404,"无此状态码");
                    break;
            }
            return $this->jsonSuccessResponse();
        }else{
            return $this->jsonErrorResponse(404,"状态必须填写");
        }
    }
}