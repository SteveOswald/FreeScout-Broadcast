<?php

namespace Modules\Broadcast\Entities;

use App\Customer;
use Illuminate\Database\Eloquent\Model;

class BroadcastList extends Model
{
    protected $fillable = ['mailbox_id', 'name', 'description', 'virtual_email', 'created_by_user_id'];

    public function members()
    {
        return $this->hasMany(BroadcastListMember::class);
    }

    public function conversations()
    {
        return $this->hasMany(BroadcastConversation::class);
    }

    /**
     * The persistent pseudo-customer this list's broadcasts are attached to
     * in FreeScout, so every broadcast sent to this list shows up under the
     * same customer profile instead of creating a new "customer" each time.
     */
    public function getOrCreateVirtualCustomer()
    {
        $customer = Customer::create($this->virtual_email, [
            'first_name' => $this->name,
        ]);

        if ($customer && empty($customer->first_name)) {
            $customer->first_name = $this->name;
            $customer->save();
        }

        return $customer;
    }

    public static function generateVirtualEmail()
    {
        return 'list-'.\Illuminate\Support\Str::random(16).'@broadcast.invalid';
    }
}
