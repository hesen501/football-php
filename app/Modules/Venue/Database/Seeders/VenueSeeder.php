<?php

namespace App\Modules\Venue\Database\Seeders;

use App\Modules\User\Models\User;
use App\Modules\Venue\Enums\VenueStatus;
use App\Modules\Venue\Models\Venue;
use App\Modules\Venue\Models\VenueWorkingHour;
use Illuminate\Database\Seeder;

/**
 * Dev/demo data — see DatabaseSeeder for why this only runs outside
 * `testing`. Ten named venues (not "Venue 1"/"Venue 2") spread across four
 * cities, each with a real manager (from UserSeeder::MANAGERS) and a full
 * 7-day working-hours set, matching what VenueService::create() would have
 * produced through the API — this is what makes BookingSeeder's working-
 * hours-aware slot picking meaningful instead of "everything is always
 * open".
 *
 * Keyed by slug via updateOrCreate(), so reruns update rather than
 * duplicate.
 */
class VenueSeeder extends Seeder
{
    /**
     * One row per venue. `hours` is null for the "standard" 08:00-23:00
     * every day; otherwise an explicit override per day_of_week
     * (0=Sunday..6=Saturday) merged over that default.
     *
     * A method rather than a class const: two entries below build their
     * `hours` override with array_fill(), which isn't a valid const
     * expression in PHP.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function venues(): array
    {
        return [
            [
                'name' => 'Arena Football Center',
                'slug' => 'arena-football-center',
                'description' => 'Flagship multi-pitch complex in central Baku with floodlit outdoor pitches and a climate-controlled indoor hall.',
                'address' => '28 May Street 14',
                'city' => 'Baku',
                'latitude' => '40.3777000',
                'longitude' => '49.8461000',
                'phone' => '+994125550101',
                'email' => 'contact@arenafootball.az',
                'status' => VenueStatus::ACTIVE,
                'managers' => ['manager1@footballbooking.test'],
                'hours' => null,
            ],
            [
                'name' => 'Bakı Football Park',
                'slug' => 'baki-football-park',
                'description' => 'Riverside park with four pitches of mixed sizes, popular with weeknight five-a-side leagues.',
                'address' => 'Neftchilar Avenue 61',
                'city' => 'Baku',
                'latitude' => '40.3660000',
                'longitude' => '49.8352000',
                'phone' => '+994125550102',
                'email' => 'info@bakifootballpark.az',
                'status' => VenueStatus::ACTIVE,
                'managers' => ['manager1@footballbooking.test', 'manager2@footballbooking.test'],
                'hours' => null,
            ],
            [
                'name' => 'Olimpik Arena',
                'slug' => 'olimpik-arena',
                'description' => 'Olympic-standard training venue with an indoor hall and a full-size outdoor pitch used by local academies.',
                'address' => 'Heydar Aliyev Avenue 3',
                'city' => 'Baku',
                'latitude' => '40.4093000',
                'longitude' => '49.8671000',
                'phone' => '+994125550103',
                'email' => 'booking@olimpikarena.az',
                'status' => VenueStatus::ACTIVE,
                'managers' => ['manager2@footballbooking.test'],
                // Opens later, closes earlier than the default.
                'hours' => array_fill(0, 7, ['opens_at' => '09:00', 'closes_at' => '22:00']),
            ],
            [
                'name' => 'Neftçi Sport Complex',
                'slug' => 'neftci-sport-complex',
                'description' => 'Club-affiliated training complex with three outdoor pitches, open to public bookings outside first-team training hours.',
                'address' => 'Tbilisi Avenue 82',
                'city' => 'Baku',
                'latitude' => '40.3958000',
                'longitude' => '49.8180000',
                'phone' => '+994125550104',
                'email' => 'reservations@neftcisport.az',
                'status' => VenueStatus::ACTIVE,
                'managers' => ['manager3@footballbooking.test'],
                'hours' => null,
            ],
            [
                'name' => 'Gənclik Football Center',
                'slug' => 'genclik-football-center',
                'description' => 'Youth-focused indoor center with two climate-controlled halls, popular for corporate and birthday bookings.',
                'address' => 'Nizami Street 203',
                'city' => 'Baku',
                'latitude' => '40.3789000',
                'longitude' => '49.8524000',
                'phone' => '+994125550105',
                'email' => 'hello@genclikfootball.az',
                'status' => VenueStatus::ACTIVE,
                'managers' => ['manager4@footballbooking.test'],
                'hours' => null,
            ],
            [
                'name' => 'Xəzər Arena',
                'slug' => 'xezer-arena',
                'description' => 'Seafront outdoor venue with three pitches and a view of the bay — a favourite for evening bookings.',
                'address' => 'Bulvar Road 45',
                'city' => 'Baku',
                'latitude' => '40.3644000',
                'longitude' => '49.8497000',
                'phone' => '+994125550106',
                'email' => 'info@xezerarena.az',
                'status' => VenueStatus::ACTIVE,
                'managers' => ['manager3@footballbooking.test'],
                'hours' => null,
            ],
            [
                'name' => 'Nizami Sport Club',
                'slug' => 'nizami-sport-club',
                'description' => "Ganja's largest outdoor football venue, three pitches spread across a converted stadium car park.",
                'address' => 'Ataturk Avenue 12',
                'city' => 'Ganja',
                'latitude' => '40.6828000',
                'longitude' => '46.3606000',
                'phone' => '+994225550107',
                'email' => 'contact@nizamisport.az',
                'status' => VenueStatus::ACTIVE,
                'managers' => ['manager4@footballbooking.test'],
                // Closed on Mondays.
                'hours' => [1 => ['is_closed' => true]],
            ],
            [
                'name' => 'Sumqayit Arena',
                'slug' => 'sumqayit-arena',
                'description' => 'Industrial-district sports complex with one indoor and two outdoor pitches, recently renovated.',
                'address' => 'Samed Vurgun Street 9',
                'city' => 'Sumqayit',
                'latitude' => '40.5892000',
                'longitude' => '49.6685000',
                'phone' => '+994185550108',
                'email' => 'info@sumqayitarena.az',
                'status' => VenueStatus::ACTIVE,
                'managers' => ['manager5@footballbooking.test'],
                'hours' => array_fill(0, 7, ['closes_at' => '22:00']),
            ],
            [
                'name' => 'Mingəçevir Football Park',
                'slug' => 'mingacevir-football-park',
                'description' => 'Riverside two-pitch venue serving the Mingachevir reservoir area, mainly weekend leagues.',
                'address' => 'Dostluq Street 5',
                'city' => 'Mingachevir',
                'latitude' => '40.7699000',
                'longitude' => '47.0592000',
                'phone' => '+994245550109',
                'email' => 'info@mingacevirpark.az',
                'status' => VenueStatus::ACTIVE,
                'managers' => ['manager5@footballbooking.test'],
                'hours' => null,
            ],
            [
                'name' => 'Şəki Yaşıl Meydan',
                'slug' => 'seki-yasil-meydan',
                'description' => 'Small two-pitch venue in the old town, currently closed for the season while the surface is relaid.',
                'address' => 'Ipek Yolu Street 2',
                'city' => 'Shaki',
                'latitude' => '41.2000000',
                'longitude' => '47.1706000',
                'phone' => '+994245550110',
                'email' => 'info@sekiyasilmeydan.az',
                // Intentionally the one inactive venue in the demo set — good
                // for exercising "inactive venue" admin/customer-facing states.
                'status' => VenueStatus::INACTIVE,
                'managers' => ['manager5@footballbooking.test'],
                'hours' => null,
            ],
        ];
    }

    private const DEFAULT_OPENS_AT = '08:00';

    private const DEFAULT_CLOSES_AT = '23:00';

    public function run(): void
    {
        foreach (self::venues() as $data) {
            $venue = Venue::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'status' => $data['status'],
                ],
            );

            $this->attachManagers($venue, $data['managers']);
            $this->seedWorkingHours($venue, $data['hours']);
        }
    }

    /** @param array<int, string> $managerEmails */
    private function attachManagers(Venue $venue, array $managerEmails): void
    {
        $managerIds = User::query()->whereIn('email', $managerEmails)->pluck('id');

        foreach ($managerIds as $managerId) {
            if (! $venue->managers()->whereKey($managerId)->exists()) {
                $venue->managers()->attach($managerId);
            }
        }
    }

    /** @param array<int, array<string, mixed>>|null $overrides */
    private function seedWorkingHours(Venue $venue, ?array $overrides): void
    {
        foreach (range(0, 6) as $day) {
            $override = $overrides[$day] ?? [];

            $isClosed = $override['is_closed'] ?? false;

            VenueWorkingHour::query()->updateOrCreate(
                ['venue_id' => $venue->id, 'day_of_week' => $day],
                [
                    'is_closed' => $isClosed,
                    'opens_at' => $isClosed ? null : ($override['opens_at'] ?? self::DEFAULT_OPENS_AT),
                    'closes_at' => $isClosed ? null : ($override['closes_at'] ?? self::DEFAULT_CLOSES_AT),
                ],
            );
        }
    }
}
