<?php
$classPath = $modx->getOption(
    'core_path',
    null,
    MODX_CORE_PATH
) . 'components/getschemajsonld/model/ModxGetSchema.php';

if (!file_exists($classPath)) {
    return '';
}
//for clientconfig
$config = $modx->getConfig();

require_once $classPath;
if(!$modx->resource->get('published')){
    return '';
}
$resource = $modx->resource->get('id');
if(!$resource){
    return '';
}
$template = $modx->resource->get('template');
if(!$template){
    return '';
}
$schemaObj = new ModxGetSchema($modx);
switch ($template) {
    //product
    case 47:       
        $types = 'product,breadcrumblist';
        $properties = array(
            'priceCurrency' => 'RUB',            
        );
        $schemaObj->setProperties($properties);
        break;
    //contacts    
    case 37:       
        $types = 'organization,breadcrumblist';
        $properties = array(
            'addressLocality' => 'Россия, г. Санкт-Петербург',
            'postalCode' => '191119',
            'streetAddress' => 'Загородный проспект, дом 40, лит. А, пом. 2-Н',
            'email' => $config['email_site'],
            'faxNumber' => '',
            'name' => $config['site_name'],
            'telephone' => $config['phone1'],
            'image' => $config['logo'],
        );
        $schemaObj->setProperties($properties);
        break;
    //FAQ    
    case 8:       
        $types = 'faq,breadcrumblist';
        break;
    default:        
        $types = 'article,breadcrumblist';
        break;
}
return $schemaObj->getSchemas($types);