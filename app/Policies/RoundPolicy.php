<?php

namespace App\Policies;

use App\Models\Round;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoundPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Round $round)
    {
        return $user->id === $round->user_id;
    }

    public function delete(User $user, Round $round)
    {
        return $user->id === $round->user_id;
    }
}
