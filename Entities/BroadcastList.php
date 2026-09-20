<?php

namespace Modules\Broadcast\Entities;

use App\Customer;
use App\User;
use Illuminate\Database\Eloquent\Builder;
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

    public function permissions()
    {
        return $this->hasMany(BroadcastListPermission::class);
    }

    /**
     * Admins may always edit; anyone else needs an explicit can_edit grant
     * for this list.
     */
    public function userCanEdit(User $user)
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->permissions->where('user_id', $user->id)->where('can_edit', true)->isNotEmpty();
    }

    /**
     * Admins and anyone allowed to edit the list may also use it; everyone
     * else needs an explicit can_use grant.
     */
    public function userCanUse(User $user)
    {
        if ($this->userCanEdit($user)) {
            return true;
        }

        return $this->permissions->where('user_id', $user->id)->where('can_use', true)->isNotEmpty();
    }

    /**
     * Lists visible on the management page: all lists for admins, only the
     * ones a non-admin has been granted edit rights on otherwise.
     */
    public function scopeEditableBy(Builder $query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('permissions', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where('can_edit', true);
        });
    }

    /**
     * Lists selectable in the compose recipient-list picker.
     */
    public function scopeUsableBy(Builder $query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('permissions', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where(function ($q2) {
                $q2->where('can_edit', true)->orWhere('can_use', true);
            });
        });
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
