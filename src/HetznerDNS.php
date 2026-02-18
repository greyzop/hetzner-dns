<?php

namespace HetznerDNS;

class HetznerDNS {

  private $api_token;

  public function __construct(array $options){

    $this->api_token = trim($options['api_token']);

  }

  private function error($message){

    return array(
      'result' => 'error',
      'message' => $message
    );

    exit();

  }

  private function curl($method, $url, array $options = null, $body = null){

    $ch = curl_init();
    //curl_setopt($ch, CURLOPT_URL, 'https://dns.hetzner.com/api/v1' . $url);
    curl_setopt($ch, CURLOPT_URL, 'https://api.hetzner.cloud/v1' . $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //$headers = [
    //  'Auth-API-Token: ' . $this->api_token,
    //];
    $headers = [
      'Authorization: Bearer ' . $this->api_token,
    ];

    if($method == 'POST' || $method == 'PUT'){
      
      curl_setopt($ch, CURLOPT_POST, 1);

      if(!empty($body)){

        array_push($headers, 'Content-Type: text/plain');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

      } else {

        array_push($headers, 'Content-Type: application/json');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options));

      }

    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $response = curl_exec($ch);

    switch ($status) {
      case '200':
      break;

      case '400':
      $this->error('Pagination selectors are mutually exclusive');
      break;

      case '401':
      $this->error('Unauthorized');
      break;

      case '403':
      $this->error('Forbidden');
      break;

      case '404':
      $this->error('Not Found');
      break;        

      case '406':
      $this->error('Not acceptable');
      break;

      case '422':
      $this->error('Unprocessable entity');
      break;
    }

    return json_decode($response, true); //Respone in array

    curl_close($ch);

  }

  //Create DNS zone
  public function createZone($options){

    #Required Options: name

    return $this->curl('POST', '/zones', $options);

  }

  //Import DNS zone file
  public function importZoneFile($id, $body){

    #Required Options: zone_id

    return $this->curl('POST', '/zones/' . $id . '/import', [], $body);

  }

  //Export DNS zone file
  public function exportZoneFile($id){

    return $this->curl('GET', '/zones/' . $id . '/export');

  }

  //Get all DNS zones
  public function getZones(array $options = null){

    if($options){
      $query = http_build_query($options);
    } else {
      $query = '';
    }

    return $this->curl('GET', '/zones' . $query);

  }

  //Get DNS zone
  public function getZone($id){
    
    return $this->curl('GET', '/zones/' . $id);

  }

  //Delete DNS zone
  public function updateZone($id, $options){

    #Required Options: name, ttl

    return $this->curl('PUT', '/zones/' . $id, $options);

  }

  //Delete DNS zone
  public function deleteZone($id){

    return $this->curl('DELETE', '/zones/' . $id);

  }

  //Create DNS record
  public function createRecord($options){

    #Required Options: name, type, value, zone_id
    //$options['records'] = array('value' => $options['']);
    return $this->curl('POST', '/zones/' . $options['zone_id'] .'/rrsets', $options);
    
  }

  //Get all DNS records
  public function getRecords(array $options = null){

    if($options){
      $query = http_build_query($options);
    } else {
      $query = '';
    }

        $allRecords = [];
    $page = 1;
    $perPage = 100;

    do {
        $response = $this->curl(
            'GET',
            '/zones/' . $options['zone_id'] . '/rrsets?type=AAAA&type=A&per_page=' . $perPage . '&page=' . $page
        );

        // Merge current page results
        $allRecords = array_merge($allRecords, $response['rrsets']);

        // Pagination info
        $pagination = $response['meta']['pagination'];
        $page = $pagination['next_page'];
        if ($pagination['next_page'] == $pagination['last_page']) {
                $page=null;
        }

    } while ($page);

    return $allRecords;
  }

  //Get a DNS record
  public function getRecord($id){

    return $this->curl('GET', '/records/' . $id);

  }

  //Update DNS record
  public function updateRecord($recordId,$options){

    #Required Options: name, type, value, zone_id

    return $this->curl('POST', '/zones/' . $options['zone_id'] .'/rrsets/'. $recordId .'/actions/set_records', $updates);

  }

  //Delete DNS record
  public function deleteRecord($options){

    #Required Options: name, type, zone_id

    return $this->curl('DELETE', '/zones/'.$options['zone_id'].'/rrsets/' . $options['name'] . '/' . $options['type']);

  }

}
