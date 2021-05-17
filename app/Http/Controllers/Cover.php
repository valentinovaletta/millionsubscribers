<?php
namespace App\Http\Controllers;

use App\Library\VkapiController;

class Cover extends Controller{

    public function index(){

        $cover = new VkapiController();
        return $cover->checkMembers();
        
    }

} 