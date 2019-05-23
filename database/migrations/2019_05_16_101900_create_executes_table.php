<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExecutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('executes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('exe');
            $table->string('code');
            $table->unsignedBigInteger('code_id');
            $table->double('memoryusage');
            $table->double('registerusage');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('executes');
    }
}
