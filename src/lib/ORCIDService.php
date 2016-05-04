<?php

/**
 * JSKOS-API Wrapper to ORCID.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;

class ORCIDService extends Service {
    use IDTrait;
    use LuceneTrait;

    protected $supportedParameters = ['notation','search'];

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

    protected function getProfile( $id )
    { 
        $token = $this->getOAuthToken();
        if (!$token) return;

        $response = Unirest\Request::get(
            "https://pub.orcid.org/v1.2/$id/orcid-bio/",
            [
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/orcid+json'
            ]
        );

        if ($response->code == 200) {
            return $response->body->{'orcid-profile'};
        }
    }

    protected function searchProfiles( $query ) 
    {
        $token = $this->getOAuthToken();
        if (!$token) return;

        $response = Unirest\Request::get(
            "https://pub.orcid.org/v1.2/search/orcid-bio/",
            [
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/orcid+json'
            ],
            [ 'q' => $this->luceneQuery('text',$query) ]
        );

        if ($response->code == 200) {
            return $response->body->{'orcid-search-results'};
        }
    }

    /**
     * See <https://members.orcid.org/api/xml-orcid-bio> for reference.
     */
    public function mapProfile($profile) {
        if (!$profile) return;

        $jskos = new Concept([
            'uri' => $profile->{'orcid-identifier'}->{'uri'},
            'notation' => [ $profile->{'orcid-identifier'}->{'path'} ],
        ]);

        $bio = $profile->{'orcid-bio'};
        $details = $bio->{'personal-details'};

        $otherNames = [];
        $name = $details->{'given-names'}->value; # required

        if (isset($details->{'family-name'})) {
            $name = "$name " . $details->{'family-name'}->value;
        }

        if (isset($details->{'credit-name'})) {
            $creditName = $details->{'credit-name'}->value;
            if ($creditName != $name) {
                $otherNames[] = $name;
                $name = $creditName;
            }
        }

        $jskos->prefLabel = ['en' => $name ];

        if (isset($details->{'other-names'})) {
            foreach ( $details->{'other-names'}->{'other-name'} as $otherName ) {
                if ($otherName->value != $name) {
                    $otherNames[] = $otherName->value;
                }
            }
        }

        if (count($otherNames)) {
            $jskos->altLabel = ['en' => $otherNames];
        }

        return $jskos;
    }

    /**
     * Perform query.
     */ 
    public function query($query) {

        $id = $this->idFromQuery($query, 
            '/^http:\/\/orcid\.org\/(\d\d\d\d-){3}\d\d\d[0-9X]$/', 
            '/^(\d\d\d\d-){3}\d\d\d[0-9X]$/'
        );

        // get concept by ORCID number or URI
        if (isset($id)) {
            $profile = $this->getProfile($id);
            $jskos = $this->mapProfile($profile);
            return $jskos;
        } 
        // search ORCID profile
        elseif(isset($query['search'])) {
            $result = $this->searchProfiles($query['search']);
            if (!$result) return;

            $concepts = [];
            foreach ($result->{'orcid-search-result'} as $bio) {
                $concepts[] = $this->mapProfile($bio->{'orcid-profile'});
            }

            return new Page($concepts,0,1,$result->{'num-found'});
        }

        return;
    }
}
