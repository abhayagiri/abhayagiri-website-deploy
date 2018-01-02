<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Site;

class CreateSitesData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Site::create([
            'name' => 'Production (Live)',
            'stage' => 'production',
            'url' => 'https://www.abhayagiri.org/',
        ]);
        Site::create([
            'name' => 'Staging',
            'stage' => 'staging',
            'url' => 'https://staging.abhayagiri.org/',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('sites')->delete();
    }
}
