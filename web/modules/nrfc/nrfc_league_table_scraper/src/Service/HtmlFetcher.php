<?php

namespace Drupal\nrfc_league_table_scraper\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class HtmlFetcher {

  protected ClientInterface $httpClient;

  // Inject the Guzzle client
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  // Fetch HTML from a given URL

  /**
   * @throws GuzzleException
   */
  public function fetchHtml($url): string
  {
    $response = $this->httpClient->request('GET', $url);
    return $response->getBody()->getContents();
  }
}
