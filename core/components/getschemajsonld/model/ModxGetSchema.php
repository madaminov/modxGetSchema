<?php
class ModxGetSchema
{
    protected $modx;
    public $resource;
    public $schemas = [];
    public $properties = [];
    public $site_url;
    
    public function __construct(modX $modx)
    {
        $this->modx = $modx;
        if ($this->modx->resource) {
            $res_id= $this->modx->resource->get('id');
            $this->resource = $this->modx->getObject('modResource', $res_id);           
        }  
        $this->site_url = $this->modx->getOption('site_url');
        $this->site_url = mb_substr($this->site_url, 0, -1);;      
    }
    public function setProperties($properties){
        $this->properties = $properties;
    }
    public function getSchemas($types){  
           
        $types = explode(',', $types);
        foreach($types as $type){
            switch ($type) {
                case 'breadcrumblist':
                    $schema = $this->getBCrumbs();
                    break;
                case 'product': 
                    $schema = $this->getProductSchema();
                    break;
                case 'organization':
                    $schema = $this->getOrgSchema();
                    break;               
                case 'article':
                    $schema = $this->getArticleSchema();
                    break;
                case 'faq':
                    $schema = $this->getFaqSchema();
                    break;
                case 'itemList':
                    $schema = $this->getItemListSchema();
                    break;
            }
            $schema = array_merge($schema);
            $arr[] = $schema;
        }

        $output = json_encode($arr, JSON_UNESCAPED_UNICODE);

        //fenom issue
        $output = preg_replace('/{/', '{ ', $output);
        $output = preg_replace('/}/', ' }', $output);

        //return only json
        $output = "<script type=\"application/ld+json\">" . $output . "</script>";

        return $output;
    }
    public function getBCrumbs()
    {
        $limit = 10;
        $count = 1;
        $list = [];

        $arr["@context"] = "https://schema.org";
        $arr["@type"] = "BreadcrumbList";

        $parentIds = $this->modx->getParentIds($this->resource->id, $limit, array('context' => $this->modx->context->key));
        $parentIds = array_reverse($parentIds);

        foreach ($parentIds as $pid) {
            if (!$pid) {
                $pid = $this->modx->getOption('site_start');
                if (!$pid) {
                    continue;
                }
            }

            $obj = $this->modx->getObject('modResource', $pid);
           
            $list[] = [
                '@type' => 'ListItem',
                'position' => $count,
                "item" => [
                    "@id" => $this->modx->makeUrl($obj->id, '', '', 0),
                    "name" => $obj->pagetitle
                ]
            ];

            $count++;
        }

        $arr['itemListElement'] = $list;

        return $arr;
    }
    
    public function getArticleSchema(){
        $arr["@context"] = "https://schema.org";
        $arr["@type"] = "Article";
        $arr['headline'] = $this->resource->get('pagetitle');
        if($this->resource->get('longtitle')){
            $arr['alternativeHeadline'] = $this->resource->get('longtitle');
        }
        if($this->resource->get('description')){
            $arr['description'] = $this->resource->get('description');
        }
        if($this->resource->getTVValue('seo_description')){
            $arr['description'] = $this->resource->getTVValue('seo_description');
        }
        $arr['datePublished'] = $this->resource->get('publishedon');
        $arr['dateCreated'] = $this->resource->get('publishedon');

        //images
        $images = [];
        $image = $this->resource->getTVValue('image');
        if($image){
            $images[] = $this->site_url . $image;
        }
        preg_match_all('/<img[^>]+src=["\']?([^"\'>]+)["\']?[^>]*>/i', $this->resource->get('content'), $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $image = $match[1];
            if (mb_strpos($image, '/') === 0) {
                $image = $this->site_url . mb_substr($image, 1);
            } elseif (mb_strpos($image, $siteUrl) !== 0) {
                $image = $this->site_url .'/'.$image;
            }
            $images[] = $image;
        }
        $images_json = $this->resource->getTVValue('images_grid_content');
        if($images_json){            
            $images_array = json_decode($images_json, true);           
            foreach($images_array as $value){               
                $images[] =  $this->site_url . $value['image'];
            }
        }
        $arr['image'] = $images;
        return $arr;
    }
    public function getOrgSchema(){
        $arr["@context"] = "https://schema.org";
        $arr["@type"] = "Organization";
        if($this->properties['name']){
            $arr['name'] =  $this->properties['name'];
        }
        if(count($this->properties)>0){
            $arr['address']["@type"] = "PostalAddress";
            if($this->properties['addressLocality']){
                $arr['address']["addressLocality"] = $this->properties['addressLocality'];
            }
            if($this->properties['postalCode']){
                $arr['address']["postalCode"] = $this->properties['postalCode'];
            }
            if($this->properties['streetAddress']){
                $arr['address']["streetAddress"] = $this->properties['streetAddress'];
            } 
            if($this->properties['email']){
                $arr['email'] =  $this->properties['email'];
            }        
            if($this->properties['faxNumber']){
                $arr['faxNumber'] =  $this->properties['faxNumber'];
            } 
            if($this->properties['namel']){
                $arr['name'] =  $this->properties['name'];
            } 
            if($this->properties['telephone']){
                $arr['telephone'] =  $this->properties['telephone'];
            } 
            if($this->properties['image']){
                $arr['image'] =   $this->site_url.$this->properties['image'];
            } 
            return $arr;
        }
        return '';
    }
    public function getFaqSchema(){
       
        $arr["@context"] = "https://schema.org";
        $arr["@type"] = "FAQPage";      
        $name = $this->resource->getTVValue('faq');
        if(!$name){
            $name = $this->resource->get('pagetitle');
        }
        $answer = $this->resource->get('content');
        $arr["mainEntity"]=[
            '@type'=>'Question',
            'name'=> $name,
            'acceptedAnswer'=>[
                '@type'=> 'Answer',
                'text' => $answer,
            ]
        ];
        return $arr;
    }
    public function getProductSchema(){
        $arr["@context"] = "https://schema.org";
        $arr["@type"] = "Product";
        $product = $this->modx->getObject('msProduct', $this->resource->id);
        $arr['name'] = $this->resource->get('pagetitle');
        if($this->resource->get('description')){
            $arr['description'] = $this->resource->get('description');
        }
        if($this->resource->getTVValue('seo_description')){
            $arr['description'] = $this->resource->getTVValue('seo_description');
        }
        $parent_id = $this->resource->get('parent');
        if ($parent_id != 0) {
            $arr['category'] = $this->modx->getObject('modResource', $parent_id)->pagetitle;
        } 
        if($product->get('article')){
            $arr['sku'] = $product->get('article');
        }
        
        $arr['image'] = $this->site_url.$product->get('image');

        $vendor_id = $product->get('vendor');
        if($vendor_id){
            $vendor = $this->modx->getObject('msVendor', $vendor_id);
            $arr['manufacturer'] = [
                '@type' => 'Organization',
                'name' => $vendor->get('name')
            ];
        }
        
        $color_arr = $product->get('color');
        if ($color_arr) {
            $arr['color'] = $color_arr[0];
        } 
        $arr['offers'] = [
            '@type' => 'Offer',
            'availability' => 'https://schema.org/InStock',
            'price' => $product->get('price'),
            'priceCurrency' =>  $this->properties['priceCurrency']
        ];
        return $arr;
    }
    public function getItemListSchema(){
        $count = 1;
        $arr["@context"] = "https://schema.org";
        $arr["@type"] = "ItemList"; 
        $arr["name"] = $this->resource->get('pagetitle');
        if($this->resource->get('longtitle')){
            $arr["name"] = $this->resource->get('longtitle');
        }        
        if($this->properties['parents']){
            $parents = explode(',', $this->properties['parents']);
            $resources = $this->modx->getCollection('modResource', array('parent:In' => $parents, 'published' => 1, 'class_key:IN' => array('modDocument', 'msCategory')));

        }else{
            $resources = $this->modx->getCollection('modResource', array('parent:In' => $this->resource->id, 'published' => 1, 'class_key:IN' => array('modDocument', 'msCategory')));
        } 
       if($resources){
            foreach ($resources as $resource) { 
                $list[] = [
                    '@type' => 'ListItem',
                    'position' => $count,
                    "item" => [
                        "@id" => $this->modx->makeUrl($resource->get('id'), '', '', 0),
                        "name" => $resource->get('pagetitle')
                    ]
                ];
                $count++;
            }
            $arr['itemListElement'] = $list;
       }
       return $arr;
       
    }
}