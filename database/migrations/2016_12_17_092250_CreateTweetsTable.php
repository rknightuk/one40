<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTweetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tweets', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('userid', 100);
            $table->string('tweetid', 100)->unique('tweetid');
            $table->boolean('type')->default(0);
            $table->integer('time')->unsigned();
            $table->string('text')->index('text');
            $table->string('source');
            $table->boolean('favorite')->default(0);
            $table->text('extra');
            $table->text('coordinates');
            $table->text('geo');
            $table->text('place');
            $table->text('contributors');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tweets');
    }
}
