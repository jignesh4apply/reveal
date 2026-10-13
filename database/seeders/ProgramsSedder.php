<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramsSedder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            [
                'id'    => 1,
                'category_id'   => 2,
                'program_tag'   => 'Fitness|Cardio',
                'title' =>'Weight Loss',
                'description'   =>'Loose Weight',
                'objective' =>'Loose Weight',
                'slug'  =>'weight-loss',
                'trailer'   =>0x70726f6772616d2f747261696c6572732f313731373438363637392e6d7034,
                'video' =>0x70726f6772616d5f766964656f732f766964656f2f353533383133372d68645f313932305f313038305f32356670732e6d70342e6d70347c70726f6772616d5f766964656f732f766964656f2f353735323732392d7568645f333834305f323136305f33306670732e6d70342e6d7034,
                'level_id'  =>3,
                'program_level_points'  =>12,
                'body_area' =>'Full Body',
                'duration'  =>'2',
                'frequency' => '2',
                'cardio_id' => 11,
                'muscle_strength_id'    => 1,
                'free_access'   =>1,
                'status'    =>1
            ],
            [
                'id'    => 2,
                'category_id'   => 2,
                'program_tag'   => 'Cardio|Test Tag',
                'title' =>'New Program',
                'description'   =>'New Program',
                'objective' =>'New Program',
                'slug'  =>'new-program',
                'trailer'   =>0x70726f6772616d2f747261696c6572732f313731373438363637392e6d7034,
                'video' =>0x70726f6772616d5f766964656f732f766964656f2f353533383133372d68645f313932305f313038305f32356670732e6d70342e6d70347c70726f6772616d5f766964656f732f766964656f2f353735323732392d7568645f333834305f323136305f33306670732e6d70342e6d7034,
                'level_id'  =>1,
                'program_level_points'  =>7,
                'body_area' =>'Stomach',
                'duration'  =>'2',
                'frequency' => '2',
                'cardio_id' => 13,
                'muscle_strength_id'    => 1,
                'free_access'   =>1,
                'status'    =>1
            ],
            [
                'id'    => 3,
                'category_id'   => 2,
                'program_tag'   => 'Fitness|Cardio',
                'title' =>'Yoga',
                'description'   =>'This is a yoga program',
                'objective' =>'This is a yoga program',
                'slug'  =>'yoga',
                'trailer'   =>0x70726f6772616d2f747261696c6572732f313731373438363637392e6d7034,
                'video' =>0x70726f6772616d5f766964656f732f766964656f2f353533383133372d68645f313932305f313038305f32356670732e6d70342e6d70347c70726f6772616d5f766964656f732f766964656f2f353735323732392d7568645f333834305f323136305f33306670732e6d70342e6d7034,
                'level_id'  =>2,
                'program_level_points'  =>8,
                'body_area' =>'Full Body',
                'duration'  =>'2',
                'frequency' => '1',
                'cardio_id' => 7,
                'muscle_strength_id'    => 4,
                'free_access'   =>1,
                'status'    =>1
            ],
            [
                'id'    => 4,
                'category_id'   => 2,
                'program_tag'   => 'Fitness',
                'title' =>'Test Program',
                'description'   =>'Test Program',
                'objective' =>'Test Program',
                'slug'  =>'test-program',
                'trailer'   =>0x70726f6772616d2f747261696c6572732f313731373438363637392e6d7034,
                'video' =>0x70726f6772616d5f766964656f732f766964656f2f353533383133372d68645f313932305f313038305f32356670732e6d70342e6d70347c70726f6772616d5f766964656f732f766964656f2f353735323732392d7568645f333834305f323136305f33306670732e6d70342e6d7034,
                'level_id'  =>1,
                'program_level_points'  =>7,
                'body_area' =>'Stomach',
                'duration'  =>'2',
                'frequency' => '2',
                'cardio_id' => 2,
                'muscle_strength_id'    => 2,
                'free_access'   =>1,
                'status'    =>1
            ],
            [
                'id'    => 5,
                'category_id'   => 2,
                'program_tag'   => 'Cardio|Sport',
                'title' =>'Cardio Program',
                'description'   =>'Cardio Program',
                'objective' =>'Cardio Program',
                'slug'  =>'cardio',
                'trailer'   =>0x70726f6772616d2f747261696c6572732f313731373438363637392e6d7034,
                'video' =>0x70726f6772616d5f766964656f732f766964656f2f353533383133372d68645f313932305f313038305f32356670732e6d70342e6d70347c70726f6772616d5f766964656f732f766964656f2f353735323732392d7568645f333834305f323136305f33306670732e6d70342e6d7034,
                'level_id'  =>2,
                'program_level_points'  =>8,
                'body_area' =>'Full Body',
                'duration'  =>'3',
                'frequency' => '2',
                'cardio_id' => 5,
                'muscle_strength_id'    => 4,
                'free_access'   =>1,
                'status'    =>1
            ],
            [
                'id'    => 6,
                'category_id'   => 3,
                'program_tag'   => 'Fitness|Gym|Cardio',
                'title' =>'Fat Control Program',
                'description'   =>'Fat Control Program',
                'objective' =>'Fat Control Program',
                'slug'  =>'fat-control-program',
                'trailer'   =>0x70726f6772616d2f747261696c6572732f313731373438363637392e6d7034,
                'video' =>0x70726f6772616d5f766964656f732f766964656f2f353533383133372d68645f313932305f313038305f32356670732e6d70342e6d70347c70726f6772616d5f766964656f732f766964656f2f353735323732392d7568645f333834305f323136305f33306670732e6d70342e6d7034,
                'level_id'  =>2,
                'program_level_points'  =>8,
                'body_area' =>'Full Body',
                'duration'  =>'2',
                'frequency' => '2',
                'cardio_id' => 2,
                'muscle_strength_id'    => 2,
                'free_access'   =>1,
                'status'    =>0
            ],
        ];

        Program::insert($programs);
    }
}
