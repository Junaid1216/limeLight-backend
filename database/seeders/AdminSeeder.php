<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Admin::create([
            'name'=>'admin',
            'email'=>'admin@gmail.com',
            'phone'=>'03015262920',
            'password'=>bcrypt(12345678),
            'image'=>'abc',
        ]);
    }
}
