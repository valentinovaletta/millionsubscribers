<?php
namespace App\Library;

class VKAPI {

    private $v = '5.130';
    private $groupId;
    private $token;

    private $subscribers = [];
    private $subscribersId = [];

    public function __construct($groupId, $token){
        $this->token = $token;
        $this->groupId = $groupId;
    }

    public function setSubscribers(){
        $data = [
            'group_id' => $this->groupId,
            'sort' => 'time_desc',
            'fields' => 'photo_max'
        ];
        $members = $this->apiRequest('groups.getMembers', $data);

        foreach($members['response']['items'] as $member){
            $this->subscribers[] = $member;
            $this->subscribersId[] = $member['id'];
        }

    }
    public function getSubscribers(){
        return $this->subscribers;
    }
    public function getSubscribersId(){
        return $this->subscribersId;
    }

    private function apiRequest($method, $data = array()) {
        $data['v'] = $this->v;
        $data['access_token'] = $this->token;

        $string = http_build_query($data);
        $url = 'https://api.vk.com/method/'.$method.'?';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url.urldecode($string));
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );        
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

}