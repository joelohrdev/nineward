<?php

namespace App\Actions\Players;

use App\Actions\Guardians\FindOrCreateGuardian;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class CreatePlayer
{
    public function __construct(private FindOrCreateGuardian $findOrCreateGuardian) {}

    /**
     * Add a player to the master list along with their guardians.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<array{first_name: string, last_name: string, email: string, phone: string|null, relationship: string|null}>  $guardians
     */
    public function handle(array $attributes, array $guardians): Player
    {
        return DB::transaction(function () use ($attributes, $guardians): Player {
            $player = Player::create($attributes);

            foreach ($guardians as $details) {
                $guardian = $this->findOrCreateGuardian->handle([
                    'first_name' => $details['first_name'],
                    'last_name' => $details['last_name'],
                    'email' => $details['email'],
                    'phone' => $details['phone'],
                ]);

                $player->guardians()->syncWithoutDetaching([
                    $guardian->id => ['relationship' => $details['relationship']],
                ]);
            }

            return $player;
        });
    }
}
