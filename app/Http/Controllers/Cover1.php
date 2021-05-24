<?php

namespace App\Http\Controllers;
use App\Library\VKAPI;

class Cover1 extends Controller
{
    public function index(){
        $cover = new VKAPI('97974818','1b47e4e0a8f334125b8a7d51ff804feb2f72b77b830317a4f3c17553797ef211aafa1beadd93d2146dea5');
        $cover->setSubscribers();
        return $cover->getSubscribers();
    }
}
