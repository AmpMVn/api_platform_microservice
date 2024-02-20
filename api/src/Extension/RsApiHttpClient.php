<?php

namespace App\Extension;

use Symfony\Component\HttpFoundation\Request;
use \Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class RsApiHttpClient implements HttpClientInterface
{

    private $decoratedClient;

    private $data;

    public function __construct(HttpClientInterface $rsApiClient)
    {
        $this->decoratedClient = $rsApiClient;
    }

    public function request(string $method, string $url, array $options = []) : ResponseInterface
    {
        if (!isset($options['headers']['X-Client-ID'])) {
            $options['headers']['X-Client-ID'] = "5944";
        }

        $response = $this->decoratedClient->request($method, $url, $options);

//        dd($response);

        return $response;
    }

    public function stream($responses, float $timeout = null) : ResponseStreamInterface
    {
        return $this->decoratedClient->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        $this->decoratedClient->withOptions($options);
        return $this;
    }

    public function write(string $url, array $options = []) : void
    {
        $data = json_decode($this->request(Request::METHOD_POST, $url, $options)->getContent());

        $this->setData($data);

        return;
    }

    public function read(string $url, array $options = []) : void
    {
//        dd($url);
        $data = json_decode($this->request(Request::METHOD_GET, $url, $options)->getContent());
        $this->setData($data);

        return;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     *
     * @return RsApiHttpClient
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function getBody()
    {
        return $this->getData()->body;
    }


//
//    public function readById(string $className, int $id, array $options = []) : object
//    {
//        return $this->readFromApiObject(Request::METHOD_GET, $className, $id, $options);
//    }

    public function getClient($clientId)
    {

        $url = 'q/appclient/getdetail?id=' . $clientId;

        $this->read($url);
        $data = $this->getData();

        return $data;
    }

    public function getClients($limit = 0, $offset = 0)
    {

        $url = 'q/appclient/getall?limit=' . $limit . '&offset=' . $offset;

        $this->read($url);
        $data = $this->getData();

        return $data;
    }

    public function getArticleBookings($articleId)
    {
        $url = 'q/appcalendar/getallarticleentriesbyarticleid';

        $options['query'] = [
            'articleId' => $articleId,
        ];

        $this->read($url, $options);
        $data = $this->getData();

        return $data;
    }

    public function getTagFilter($remoteClientId)
    {
        $url = 'q/appsettingstagfilter/getall?clientId=' . $remoteClientId;
        $options['headers']['X-Client-ID'] = $remoteClientId;

        $this->read($url, $options);
        $data = $this->getData();

        return $data;
    }

    public function getTagFilterDetail($remoteTagFilterId, $remoteClientId)
    {
        $url = 'q/appsettingstagfilter/getcomplete?clientId=' . $remoteClientId . '&id=' . $remoteTagFilterId;
        $options['headers']['X-Client-ID'] = $remoteClientId;

        $this->read($url, $options);
        $data = $this->getData();

        return $data;
    }

    public function getArticlesByClientId($remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'pageCount' => "2000",
            'pageOffset' => "0",
            "searchString" => "",
        ];

        $this->read('q/apparticle/getall', $options);
        $data = $this->getData();

        return $data;
    }

    public function getDeletedArticlesByClientId($remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'pageCount' => "2000",
            'pageOffset' => "0",
            "searchString" => "",
            "deleted" => 1
        ];

        $this->read('q/apparticle/getall', $options);
        $data = $this->getData();

        return $data;
    }

    public function getPricesForArticle($remoteClientId, $remoteArticleId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'type' => "article",
            'article_id' => $remoteArticleId,
        ];

        $this->read('q/appcalculation/getcalculationsingle', $options);
        $data = $this->getData();

        return $data;
    }

    public function getPriceGroupsForArticle($remoteClientId, $remoteArticleId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'type' => "article",
            'articleId' => $remoteArticleId,
        ];

        $this->read('q/apparticlepricegroup/getallbyarticleid', $options);
        $data = $this->getData();

        return $data;
    }

    public function getPriceGroupsForObject($remoteClientId, $remoteObjectId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'type' => "article",
            'objectId' => $remoteObjectId,
        ];

        $this->read('q/appobjectpricegroup/getallbyobjectid', $options);
        $data = $this->getData();

        return $data;
    }

    public function getStocksForArticle($remoteClientId, $remoteArticleId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'id' => $remoteArticleId,
        ];

        $this->read('q/apparticlestock/getallbyarticleid', $options);
        $data = $this->getData();

        return $data;
    }

    public function getImagesForArticle($remoteClientId, $remoteArticleId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'articleId' => $remoteArticleId,
        ];

        $this->read('q/apparticleimage/getallbyarticleid', $options);
        $data = $this->getData();

        return $data;
    }

    public function getExtrasForArticle($remoteClientId, $remoteArticleId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'article_id' => $remoteArticleId,
        ];

        $this->read('q/apparticleonlinesettingsarticle/getallbyarticleid', $options);
        $data = $this->getData();

        return $data;
    }

    public function getExtrasForObject($remoteClientId, $remoteObjectId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'objectId' => $remoteObjectId,
        ];

        $this->read('q/appobjectonlinesettingsarticle/getallbyobjectid', $options);
        $data = $this->getData();

        return $data;
    }

    public function getAttributesForArticle($remoteClientId, $remoteArticleId, $remoteArticleAttributeSetId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'articleId' => $remoteArticleId,
            'articleAttributeSetId' => $remoteArticleAttributeSetId,
        ];

        $this->read('q/apparticlefeatureattributesetentry/getallbyids', $options);
        $data = $this->getData();

        return $data;
    }

    public function getOnlineBookingsForArticle($remoteArticleId, $remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'articleId' => $remoteArticleId,
        ];

        $this->read('q/apparticleexport/getall', $options);
        $data = $this->getData();

        return $data;
    }

    public function getOnlineBookingsForObject($remoteObjectId, $remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'objectId' => $remoteObjectId,
        ];

        $this->read('q/appobjectexport/getallbyobjectid', $options);
        $data = $this->getData();

        return $data;
    }

    public function getSellPriceForArticle($remoteClientId, $remoteArticleId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'article_id' => $remoteArticleId,
        ];

        $this->read('q/apparticlesellprice/getdetailbyarticleid', $options);
        $data = $this->getData();

        return $data;
    }

    public function getPriceGroupsByClientId($remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [];

        $this->read('q/appsettingspricegroup/getall', $options);
        $data = $this->getData();

        return $data;
    }

    public function getPriceDealsByClientId($remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [];

        $this->read('q/appsettingspricegroup/getall', $options);
        $data = $this->getData();

        return $data;
    }

    public function getEntriesForPricegroups($remoteClientId, $remotePricegroupId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'id' => $remotePricegroupId,
        ];

        $this->read('q/appsettingspricegroup/getallentry', $options);
        $data = $this->getData();

        return $data;
    }

    public function getLocationsByClientId($remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [

        ];

        $this->read('q/appsettingslocation/getall', $options);
        $data = $this->getData();

        return $data;
    }

    public function getStoragesByClientId($remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [

        ];

        $this->read('q/apparticlelocation/getall', $options);
        $data = $this->getData();

        return $data;
    }

    public function getStorageImagesByStorageIdAndClientId($remoteStorageId, $remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'article_location_id' => $remoteStorageId,
        ];

        $this->read('q/apparticlelocationimage/getallbyarticlelocationid', $options);
        $data = $this->getData();

        return $data;
    }

    public function getLocationImagesByStorageIdAndClientId($remoteLocationId, $remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'config_location_id' => $remoteLocationId,
        ];

        $this->read('q/appconfiglocationimage/getallbyconfiglocationid', $options);
        $data = $this->getData();

        return $data;
    }

    public function getObjectDetailsForAutovermietung($remoteObjectId, $remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'carId' => $remoteObjectId,
        ];

        $this->read('q/appobjectdetailcar/getdetail', $options);
        $data = $this->getData();

        return $data;
    }

    public function getObjectDetailsForMachine($remoteObjectId, $remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'carId' => $remoteObjectId,
        ];

        $this->read('q/appobjectdetailmachine/getdetail', $options);
        $data = $this->getData();

        return $data;
    }

    public function getObjectDetailsForCaravan($remoteObjectId, $remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'carId' => $remoteObjectId,
        ];

        $this->read('q/appobjectdetailcaravan/getdetail', $options);
        $data = $this->getData();

        return $data;
    }

    public function getObjectsByClientId($remoteClientId, $status = "")
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'page' => "0",
            'offset' => "2000",
            "searchString" => "",
            "objectStatus" => $status
        ];

        $this->read('q/appobject/getall', $options);
        $data = $this->getData();

        return $data;
    }

    public function getFreeFieldsByObjectId($remoteObjectId, $remoteClientId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'objectId' => $remoteObjectId,
            'offset' => "2000",
            "searchString" => "",
        ];

        $this->read('q/appconfigobjectdetail/getallwithvalues', $options);
        $data = $this->getData();

        return $data;
    }

    public function getObjectDetailByObjectIdId($remoteClientId, $remoteObjectId)
    {
        $options['headers']['X-Client-ID'] = $remoteClientId;
        $options['query'] = [
            'id' => $remoteObjectId,
        ];

        $this->read('q/appobject/getdetail', $options);
        $data = $this->getData();

        return $data;
    }
}
