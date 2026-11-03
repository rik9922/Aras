<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the 50 US states + DC into the `states` table.
 *
 * Bug 2 (MC cmon1a7u4005d3r1h5cdct2vh): the recap form's State dropdown was
 * empty in production because the `states` table was never seeded. This
 * seeder is idempotent — it only inserts rows that don't already exist
 * (matched by `short_name`), so it's safe to re-run.
 *
 * Column names match the `State` model and Blade views: `name` and
 * `short_name` (used as `{{ $state->short_name }}` in views and read by
 * `App\Services\Dwolla\CustomerService::getStateShortName`).
 */
class StateSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $states = [
            ['name' => 'Alabama',              'short_name' => 'AL'],
            ['name' => 'Alaska',               'short_name' => 'AK'],
            ['name' => 'Arizona',              'short_name' => 'AZ'],
            ['name' => 'Arkansas',             'short_name' => 'AR'],
            ['name' => 'California',           'short_name' => 'CA'],
            ['name' => 'Colorado',             'short_name' => 'CO'],
            ['name' => 'Connecticut',          'short_name' => 'CT'],
            ['name' => 'Delaware',             'short_name' => 'DE'],
            ['name' => 'District of Columbia', 'short_name' => 'DC'],
            ['name' => 'Florida',              'short_name' => 'FL'],
            ['name' => 'Georgia',              'short_name' => 'GA'],
            ['name' => 'Hawaii',               'short_name' => 'HI'],
            ['name' => 'Idaho',                'short_name' => 'ID'],
            ['name' => 'Illinois',             'short_name' => 'IL'],
            ['name' => 'Indiana',              'short_name' => 'IN'],
            ['name' => 'Iowa',                 'short_name' => 'IA'],
            ['name' => 'Kansas',               'short_name' => 'KS'],
            ['name' => 'Kentucky',             'short_name' => 'KY'],
            ['name' => 'Louisiana',            'short_name' => 'LA'],
            ['name' => 'Maine',                'short_name' => 'ME'],
            ['name' => 'Maryland',             'short_name' => 'MD'],
            ['name' => 'Massachusetts',        'short_name' => 'MA'],
            ['name' => 'Michigan',             'short_name' => 'MI'],
            ['name' => 'Minnesota',            'short_name' => 'MN'],
            ['name' => 'Mississippi',          'short_name' => 'MS'],
            ['name' => 'Missouri',             'short_name' => 'MO'],
            ['name' => 'Montana',              'short_name' => 'MT'],
            ['name' => 'Nebraska',             'short_name' => 'NE'],
            ['name' => 'Nevada',               'short_name' => 'NV'],
            ['name' => 'New Hampshire',        'short_name' => 'NH'],
            ['name' => 'New Jersey',           'short_name' => 'NJ'],
            ['name' => 'New Mexico',           'short_name' => 'NM'],
            ['name' => 'New York',             'short_name' => 'NY'],
            ['name' => 'North Carolina',       'short_name' => 'NC'],
            ['name' => 'North Dakota',         'short_name' => 'ND'],
            ['name' => 'Ohio',                 'short_name' => 'OH'],
            ['name' => 'Oklahoma',             'short_name' => 'OK'],
            ['name' => 'Oregon',               'short_name' => 'OR'],
            ['name' => 'Pennsylvania',         'short_name' => 'PA'],
            ['name' => 'Rhode Island',         'short_name' => 'RI'],
            ['name' => 'South Carolina',       'short_name' => 'SC'],
            ['name' => 'South Dakota',         'short_name' => 'SD'],
            ['name' => 'Tennessee',            'short_name' => 'TN'],
            ['name' => 'Texas',                'short_name' => 'TX'],
            ['name' => 'Utah',                 'short_name' => 'UT'],
            ['name' => 'Vermont',              'short_name' => 'VT'],
            ['name' => 'Virginia',             'short_name' => 'VA'],
            ['name' => 'Washington',           'short_name' => 'WA'],
            ['name' => 'West Virginia',        'short_name' => 'WV'],
            ['name' => 'Wisconsin',            'short_name' => 'WI'],
            ['name' => 'Wyoming',              'short_name' => 'WY'],
        ];

        foreach ($states as $row) {
            DB::table('states')->updateOrInsert(
                ['short_name' => $row['short_name']],
                [
                    'name'       => $row['name'],
                    'short_name' => $row['short_name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
