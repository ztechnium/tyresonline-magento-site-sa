<?php
/**
 * Ecomteck_StoreLocator extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @category  Ecomteck
 * @package   Ecomteck_StoreLocator
 * @copyright 2016 Ecomteck
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 * @author    Ecomteck
 */
namespace Ecomteck\StoreLocator\Api\Data;

/**
 * @api
 */
interface StoresInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const STOCKIST_ID         = 'stores_id';
    const NAME                = 'name';
    const ADDRESS             = 'address';
    const CITY                = 'city';
    const POSTCODE            = 'postcode';
    const REGION              = 'region';
    const EMAIL               = 'email';
    const PHONE               = 'phone';
    const LATITUDE            = 'latitude';
    const LONGITUDE           = 'longitude';
    const URL_KEY                = 'url_key';
    const STATUS              = 'status';
    const TYPE                = 'type';
    const COUNTRY             = 'country';
    const IMAGE               = 'image';
    const CREATED_AT          = 'created_at';
    const UPDATED_AT          = 'updated_at';
    const STORE_ID            = 'store_id';
    const SCHEDULE            = 'schedule';
    const INTRO               = 'intro';
    const DESCRIPTION         = 'description';
    const DISTANCE            = 'distance';
    const STATION             = 'station';
    const DETAILS_IMAGE       = 'details_image';
    const EXTERNAL_LINK       = 'external_link';
    const OPENING_HOURS       = 'opening_hours';
    const SPECIAL_OPENING_HOURS       = 'special_opening_hours';
    const CATEGORY            = 'category';
    const IS_ALL_PRODUCTS     = 'is_all_products';
    const ISMOBILEVAN         = 'ismobilevan';
    const SHIPPING_AMOUNT     = 'shipping_amount';
	const POSITION         	  = 'position';
    const PICKUP_SERVICE      = 'pickup_service';
    const EMAIL_COPY     	  = 'email_copy';
    const OPENING_HOURS_TEXT1          = 'opening_hours_text1';
    const OPENING_HOURS_TEXT2          = 'opening_hours_text2';
	const SKIP_DAYS     	  = 'skip_days';
    const NAME_RTL           = 'name_rtl';
    const ADDRESS_RTL           = 'address_rtl';
    const CITY_RTL           = 'city_rtl';
    const OPENING_HOURS_TEXT1_RTL           = 'opening_hours_text1_rtl';
    const OPENING_HOURS_TEXT2_RTL           = 'opening_hours_text2_rtl';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get schedule
     *
     * @return string
     */
    public function getSchedule();


    /**
     * Get intro
     *
     * @return string
     */
    public function getIntro();

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription();

    /**
     * Get external link
     *
     * @return string
     */
    public function getExternalLink();

    /**
     * Get distance
     *
     * @return string
     */
    public function getDistance();

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory();

    /**
     * Get is_all_products
     *
     * @return string
     */
    public function getIsAllProducts();

    /**
     * Get station
     *
     * @return string
     */
    public function getStation();

    /**
     * Get store details image
     *
     * @return string
     */
    public function getDetailsImage();


    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Get store url
     *
     * @return string
     */
    public function getUrlKey();
    
    /**
     * Get address
     *
     * @return string
     */
    public function getAddress();
    
    /**
     * Get city
     *
     * @return string
     */
    public function getCity();
    
    /**
     * Get postcode
     *
     * @return string
     */
    public function getPostcode();
    
    /**
     * Get region
     *
     * @return string
     */
    public function getRegion();
    
    /**
     * Get email
     *
     * @return string
     */
    public function getEmail();
    
    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone();
    
    /**
     * Get image
     *
     * @return string
     */
    public function getImage();
    
    /**
     * Get latitude
     *
     * @return string
     */
    public function getLatitude();
    
    /**
     * Get longitude
     *
     * @return string
     */
    public function getLongitude();

    /**
     * Get is active
     *
     * @return bool|int
     */
    public function getStatus();

    /**
     * Get type
     *
     * @return int
     */
    public function getType();

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry();

    /**
     * set id
     *
     * @param $id
     * @return StoresInterface
     */
    public function setId($id);

    /**
     * set name
     *
     * @param $name
     * @return StoresInterface
     */
    public function setName($name);

    /**
     * set url key
     *
     * @param $urlKey
     * @return StoresInterface
     */
    public function setUrlKey($urlKey);
    
    /**
     * set image
     *
     * @param $image
     * @return AuthorInterface
     */
    public function setImage($image);
    
    /**
     * set address
     *
     * @param $address
     * @return StoresInterface
     */
    public function setAddress($address);

    /**
     * set city
     *
     * @param $city
     * @return StoresInterface
     */
    public function setCity($city);

    public function setIsmobilevan($ismobilevan);

    public function setShippingAmount($shipping_amount);
	
    public function setPosition($position);
	
    public function setPickupService($pickup_service);
	
    public function setEmailCopy($email_copy);

    public function setOpeningHoursText1($opening_hours_text1);

    public function setOpeningHoursText2($opening_hours_text2);
	
    public function setSkipDays($skip_days);

    public function setNameRtl($name_rtl);

    public function setAddressRtl($address_rtl);

    public function setCityRtl($city_rtl);

    public function setOpeningHoursText1Rtl($opening_hours_text1_rtl);

    public function setOpeningHoursText2Rtl($opening_hours_text2_rtl);

    /**
     * set postcode
     *
     * @param $postcode
     * @return StoresInterface
     */
    public function setPostcode($postcode);


    /**
     * set schedule
     *
     * @param $schedule
     * @return StoresInterface
     */
    public function setSchedule($schedule);

    /**
     * set category
     *
     * @param $category
     * @return StoresInterface
     */
    public function setCategory($category);

    /**
     * set is_all_products
     *
     * @param $isAllProducts
     * @return StoresInterface
     */
    public function setIsAllProducts($isAllProducts);

    /**
     * set description
     *
     * @param $description
     * @return StoresInterface
     */
    public function setDescription($description);

    /**
     * set distance
     *
     * @param $distance
     * @return StoresInterface
     */
    public function setDistance($distance);

    /**
     * set station
     *
     * @param $station
     * @return StoresInterface
     */
    public function setStation($station);

    /**
     * set external link
     *
     * @param $external_link
     * @return StoresInterface
     */
    public function setExternalLink($external_link);

    /**
     * set intro
     *
     * @param $intro
     * @return StoresInterface
     */
    public function setIntro($intro);

    /**
     * set store details image
     *
     * @param $details_image
     * @return StoresInterface
     */
    public function setDetailsImage($details_image);

    /**
     * set region
     *
     * @param $region
     * @return StoresInterface
     */
    public function setRegion($region);

    /**
     * set email
     *
     * @param $email
     * @return StoresInterface
     */
    public function setEmail($email);
    
    /**
     * set phone
     *
     * @param $phone
     * @return StoresInterface
     */
    public function setPhone($phone);

    /**
     * set latitude
     *
     * @param $latitude
     * @return StoresInterface
     */
    public function setLatitude($latitude);
    
    /**
     * set longitude
     *
     * @param $longitude
     * @return StoresInterface
     */
    public function setLongitude($longitude);

    /**
     * Set status
     *
     * @param $status
     * @return StoresInterface
     */
    public function setStatus($status);

    /**
     * set type
     *
     * @param $type
     * @return StoresInterface
     */
    public function setType($type);

    /**
     * Set country
     *
     * @param $country
     * @return StoresInterface
     */
    public function setCountry($country);

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * set created at
     *
     * @param $createdAt
     * @return StoresInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * set updated at
     *
     * @param $updatedAt
     * @return StoresInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @param $storeId
     * @return StoresInterface
     */
    public function setStoreId($storeId);

    /**
     * @return int[]
     */
    public function getStoreId();

    /**
     * set opening hours
     *
     * @param $openingHours
     * @return StoresInterface
     */
    public function setOpeningHours($openingHours);

    /**
     * get opening hours
     *
     * @return Array
     */
    public function getOpeningHours();

    /**
     * get opening hours
     *
     * @return Array
     */
    public function getOpeningHoursConfig();
    

    /**
     * get opening hours formated
     *
     * @return string
     */
    public function getOpeningHoursFormated();

    /**
     * set special opening hours
     *
     * @param $specialOpeningHours
     * @return StoresInterface
     */
    public function setSpecialOpeningHours($specialOpeningHours);

    /**
     * get special opening hours
     *
     * @return Array
     */
    public function getSpecialOpeningHours();

    /**
     * get special opening hours formated
     *
     * @return string
     */
    public function getSpecialOpeningHoursFormated();

    /**
     * get special opening hours
     *
     * @return Array
     */
    public function getSpecialOpeningHoursConfig();

}
