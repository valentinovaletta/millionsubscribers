<?php
namespace App\Library;
use App\Models\Subscriber;
use Image;

class VkApiController {
    private $v = '5.130';
    private $token = '1b47e4e0a8f334125b8a7d51ff804feb2f72b77b830317a4f3c17553797ef211aafa1beadd93d2146dea5';
    private $groupId = 97974818;

    private $dbMembers = [];
    private $dbMembersIds = [];
    private $vkMembers = [];
    private $vkMembersIds = [];

    public function checkMembers(){
        $this->setDbMembers();
        $dbCount = $this->getDbMembersCount();
        $vkMembers = $this->setVkMembers();
        $vkCount = $vkMembers['count'];

        if( $dbCount == $vkCount ){
            $img = Image::make(public_path('storage/0.png'));
            $img->text($vkCount.' subscribers', 250, 100, function($font) {  
                $font->file(public_path('storage/font.ttf'));  
                $font->size(64);  
                $font->color('#e1e1e1');  
                $font->align('center');  
                $font->valign('bottom');  
            });
            $img->text( (1000 - $vkCount).' to go', 250, 150, function($font) {  
                $font->file(public_path('storage/font.ttf'));  
                $font->size(32);  
                $font->color('#e1e1e1');  
                $font->align('center');  
                $font->valign('bottom');  
            });

            $userAvatar1 = Image::make($this->dbMembers[0]['photo']);
            $userAvatar1->resize(50, 50);

            $userAvatar2 = Image::make($this->dbMembers[1]['photo']);
            $userAvatar2->resize(50, 50);
            
            $userAvatar3 = Image::make($this->dbMembers[2]['photo']);
            $userAvatar3->resize(50, 50);

            $img->insert($userAvatar1,'', 465, 100);
            $img->insert($userAvatar2,'', 565, 100);
            $img->insert($userAvatar3,'', 665, 100);

            $img->save(public_path('storage/cover.png'));
            $this->changeCover();
            return  $dbCount;
        }

        if( $dbCount > $vkCount ){ //someone leaved
            $img = Image::make(public_path('storage/0.png'));
            $img->text(' -1 subscriber', 250, 100, function($font) {  
                $font->file(public_path('storage/font.ttf'));  
                $font->size(64);  
                $font->color('#de2121');
                $font->align('center');  
                $font->valign('bottom');  
            });
            $img->text( (1000 - $vkCount).' to go', 250, 150, function($font) {  
                $font->file(public_path('storage/font.ttf'));  
                $font->size(32);  
                $font->color('#e1e1e1');  
                $font->align('center');  
                $font->valign('bottom');  
            });
            $img->text( ' ;( ', 600, 150, function($font) {  
                $font->file(public_path('storage/font.ttf'));  
                $font->size(98);  
                $font->color('#de2121');
                $font->align('center');  
                $font->valign('bottom');  
            });
            $img->save(public_path('storage/cover.png'));
            $this->changeCover();
            return $this->remSubscriber();
        }
        if( $dbCount < $vkCount ){ //someone came
            $img = Image::make(public_path('storage/0.png'));
            $img->text(' +1 subscriber', 250, 100, function($font) {  
                $font->file(public_path('storage/font.ttf'));  
                $font->size(64);  
                $font->color('#17d446');
                $font->align('center');  
                $font->valign('bottom');  
            });
            $img->text( (1000 - $vkCount).' to go', 250, 150, function($font) {  
                $font->file(public_path('storage/font.ttf'));  
                $font->size(32);  
                $font->color('#e1e1e1');  
                $font->align('center');  
                $font->valign('bottom');  
            });
            $img->text( ' :) ', 600, 150, function($font) {  
                $font->file(public_path('storage/font.ttf'));  
                $font->size(98);  
                $font->color('#17d446');
                $font->align('center');  
                $font->valign('bottom');  
            });
            $img->save(public_path('storage/cover.png'));
            $this->changeCover();
            return $this->addSubscriber();
        }
    }

    private function changeCover(){
        $cover_path = "storage/cover.png";
        $photo = curl_file_create($cover_path,'image/jpeg');

        try {
            $getServerUrl = $this->apiRequest(
                "photos.getOwnerCoverPhotoUploadServer",
                ["group_id" => $this->groupId,"crop_x2" => 1590, "crop_y2" => 400]
            );
          } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        $url = $getServerUrl['response']['upload_url'];

        try {
            $upload = $this->photoRequest($url, ["photo" => $photo,"access_token" => $this->token, "v"=>'5.21']);
            $save = $this->photoRequest("https://api.vk.com/method/photos.saveOwnerCoverPhoto?hash=".$upload['hash']."&photo=".$upload['photo']."&access_token=".$this->token."&v=".$this->v);
        } catch(\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $save;
    }

    private function remSubscriber(){
        $leaved = array_diff($this->dbMembersIds, $this->vkMembersIds );
        foreach($leaved as $user){
            Subscriber::where('subscriber_id', $user)
            ->update(['status' => 0]);                
        }
        return $leaved;
    }

    private function addSubscriber(){
        $diff = array_diff($this->vkMembersIds, $this->dbMembersIds);
        foreach($diff as $user){
            $key = $this->searchForId($user, $this->vkMembers);
            Subscriber::upsert([
                [
                    'subscriber_id' => $this->vkMembers[$key]['id'],
                    'first_name'    => $this->vkMembers[$key]['first_name'],
                    'last_name'     => $this->vkMembers[$key]['last_name'],
                    'photo'         => $this->vkMembers[$key]['photo_max'],
                    'status'        => 1
                ]
            ], ['subscriber_id'], ['status']);
        }
        return $diff;        
    }

    private function searchForId($id, $array) {
        foreach ($array as $key => $val) {
            if ($val['id'] === $id) {
                return $key;
            }
        }
        return null;
     }

    private function setDbMembers(){
        $members = Subscriber::where('status', 1)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->toArray();
        foreach($members as $member) { 
            $this->dbMembers[] = $member;
            $this->dbMembersIds[] = $member['subscriber_id'];
        }
        return $this->dbMembers;
    }

    private function getDbLastMember(){
        return Subscriber::Select('subscriber_id')
               ->orderBy('created_at')
               ->take(1)
               ->get()->first()->subscriber_id;
    }    

    private function getDbMembersCount(){
        return Subscriber::where('status', 1)->count();
    }    

    private function setVkMembers(){
        $data = [
            'group_id' => $this->groupId,
            'sort' => 'time_desc',
            'fields' => 'photo_max'
        ];
        $members = $this->apiRequest('groups.getMembers', $data);

        foreach($members['response']['items'] as $member){
            $this->vkMembers[] = $member;
            $this->vkMembersIds[] = $member['id'];
        }

        $response = [
            'count' => $members['response']['count'],
            'last_member_id' => $members['response']['items'][0]['id']
        ];
        return $response;
        //return $this->saveMembers($members['response']['items']); // save vk members in db
    }

    private function saveMembers(Array $members){
      
        $insertMember = [];
        foreach($members as $member){
            $insertMember[] = [
                'subscriber_id' => $member['id'],
                'first_name'    => $member['first_name'],
                'last_name'     => $member['last_name'],
                'photo'         => $member['photo_max'],
                'status'        => 1
            ];
        }

        return Subscriber::insert($insertMember);
      
    }
    
    private function photoRequest($url, $data = array()) {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: multipart/form-data;"]);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/1.0 (Windows NT 6.1; WOW64; rv:28.0) Gecko/20100101 Firefox/28.0');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $responce = json_decode(curl_exec($curl), true);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);
        if (isset($error_msg)) {
            return $error_msg;
        }
        return $responce;
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
