<?php

/**
 * JSKOS-API Wrapper to ORCID.
 */

include_once realpath(__DIR__.'/../..') . '/vendor/autoload.php';
include_once realpath(__DIR__).'/IDTrait.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;

class ORCIDService extends Service {
    use IDTrait;

    protected $supportedParameters = ['notation'];

    private $client_id;
    private $client_secret;

    public function __construct() {
        $this->client_id     = getenv('ORCID_CLIENT_ID');
        $this->client_secret = getenv('ORCID_CLIENT_SECRET');
        parent::__construct();
    }

    protected function getOAuthToken() {
        if ($this->client_id and $this->client_secret) {
            $body = Unirest\Request\Body::form(
                [ 
                  'client_id' => $this->client_id, 
                  'client_secret' => $this->client_secret,
                  'scope' => '/read-public',
                  'grant_type' => 'client_credentials',
                ]
            );
            $response = Unirest\Request::post(
                'https://orcid.org/oauth/token', 
                [ 'Accept' => 'application/json' ],
                $body
            );
            if ($response->code == 200) {
                return $response->body->{'access_token'};
            }
        }
    }

    protected function getBio( $id, $token ) 
    { 
        $url = "https://pub.orcid.org/v1.2/$id/orcid-bio/";

        $response = Unirest\Request::get($url,
            [
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/orcid+json'
            ]
        );

        if ($response->code == 200) {
            return $response->body->{'orcid-profile'}->{'orcid-bio'};
        }
    }

    /**
     * Perform query.
     */ 
    public function query($query) {

        $id = $this->idFromQuery($query, 
            '/^http:\/\/orcid\.org\/(\d\d\d\d-){3}\d\d\d[0-9X]$/', 
            '/^(\d\d\d\d-){3}\d\d\d[0-9X]$/'
        );

        // get concept by notation and/or uri
        if (isset($id)) {
            $uri = "http://orcid.org/$id";
            $jskos = new Concept(['uri' => $uri, 'notation' => [$id]]);

            $token = $this->getOAuthToken();
            if (!$token) return;

            $bio = $this->getBio( $id, $token );
            if (!$bio) return;

            $details = $bio->{'personal-details'};

            # TODO: check if name actually exists
            $name = $details->{'given-names'}->value . ' ' .
                    $details->{'family-name'}->value;

            $jskos->prefLabel = ['en' => $name ];

            return $jskos;
        }

        // TODO: search for names, see
        // https://members.orcid.org/api/tutorial-searching-api-12-and-earlier

        return;
    }
}
