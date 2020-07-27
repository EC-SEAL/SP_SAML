#!/usr/bin/php
<?php

// Execute manually or over CRON.
//* * * * * export SSPROOT="/var/www/queryBridge";/usr/bin/php $SSPROOT/modules/esmo/lib/CMHandler.php >> /dev/stderr




// ---------------- Required environment variables ----------------


//Path to the root directory of the SimpleSamlPHP installation
//>$ export SSPROOT="/var/www/clave1bBridge"
$sspRoot=getenv("SSPROOT");
$sspRoot= "C:\Users\Francisco Aragó\simplesamlphp-1.18.7";





// ---------------- Internal configuration ----------------


//CM metadata set name:
$cmFile = 'esmo-configmanager';






// ---------------- Load application context ----------------

#Bootstrapper of the SSP classes
$autoload = $sspRoot.'/lib/_autoload.php';


if($sspRoot == NULL || $sspRoot == "")
    die("ERROR: Environment variable SSPROOT not set\n");

if(!is_file($autoload))
    die("ERROR: Environment variable SSPROOT not pointing to a valid SSP installation: ".$sspRoot."\n");


require_once($autoload);


//Metadata directory
$globalConfig = SimpleSAML\Configuration::getInstance();
$metadataDirectory = $globalConfig->getString('metadatadir', 'metadata/');
$metadataDirectory = $globalConfig->resolvePath($metadataDirectory) . '/';









// ---------------- Application ----------------



function getCmMetadataFile($metadataDirectory,$set){
    
    $metadataFile = $metadataDirectory.'/'.$set.'.php';
    try{
        //Don't use _once or the global variable might get unset.
        require($metadataFile);
    }catch(Exception $e){
        throw new SimpleSAML_Error_Exception("ESMO Config Manager metadata file ".$metadataFile." not found.");
    }
    
    if(!isset($esmoConfigManagerConf))
        throw new SimpleSAML_Error_Exception("Config Manager data in ".$set.": malformed or undefined global esmoConfigManagerConf variable");
    
    return SimpleSAML\Configuration::loadFromArray($esmoConfigManagerConf);
}



function getApiURL($entityMetadata,$apiClass,$apiCall){
    
    if($entityMetadata == NULL)
        throw new SimpleSAML_Error_Exception("Passed entity is null");

    if($entityMetadata->getArray('publishedAPI',NULL) == NULL)
        throw new SimpleSAML_Error_Exception("Passed entity publishedAPI not set");

    $apis = $entityMetadata->getArray('publishedAPI');

    if(sizeof($apis)<=0)
        throw new SimpleSAML_Error_Exception("Passed entity does not publish any APIs");

    $retUrl = NULL;
    foreach($apis as $api){
        if($api['apiClass'] === $apiClass
        && $api['apiCall'] === $apiCall){
            $retUrl = $api['apiEndpoint'];
            break;
        }
    }
    if ($retUrl === NULL)
        throw new Exception('Could not find an -'.$apiClass.'/'.$apiCall.' endpoint in microservice ');
    
    return $retUrl;
}





function getURL($url){

    if($url == NULL || $url == "")
        throw new SimpleSAML_Error_Exception("No url provided");
    
    $headers = array();
    $headers[] = 'Accept: application/json';
    SimpleSAML\Logger::debug('Headers passed:'.print_r($headers,true));
		
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_CAINFO, "/etc/ssl/certs/ca-bundle.crt");
    
    curl_setopt($curl, CURLINFO_HEADER_OUT, true); // For debugging, to return the raw request on the info
	
    $response = curl_exec($curl);
    
    
    //If error in the HTTP communication, throw it
    if(curl_errno($curl)){
        throw new SimpleSAML_Error_Exception(curl_error($curl));
    }
    
    $info = curl_getinfo($curl);
    //SimpleSAML\Logger::debug('Raw sent HTTP request:'.$info['request_header']);
    //SimpleSAML\Logger::debug('Raw received HTTP response:'.$response);
        
    $header_size  = $info['header_size'];
    $headerstr    = substr($response, 0, $header_size);
    $resp_headers = explode("\r\n", trim($headerstr));
    $resp_body    = substr($response, $header_size);
    
    curl_close($curl);
	
    if (strpos($info['http_code'], '2') !== 0){
        throw new SimpleSAML_Error_Exception("Server returned error HTTP code: ".$info['http_code'].' -> '.$resp_body);
    }
    //SimpleSAML\Logger::debug('Response body:'.$resp_body);
    
    return $resp_body;
}


function fetchMetadataDataset ($cmHandlerMetadata, $url){

    $keyID = sspmod_esmo_HttpSig_Client::get_sha256_fingerprint($cmHandlerMetadata->getString('rsaPublicKeyBinary',NULL));
    $clientKey = $cmHandlerMetadata->getString('rsaPrivateKeyBinary',NULL);
    $serverPubKeys = array($cmHandlerMetadata->getString('cmRsaPublicKeyBinary',NULL));


    //Create an HTTPSig client helper
    $httpsig = new sspmod_esmo_HttpSig_Fetch($keyID, $clientKey, $serverPubKeys);

    //Grab the metadata set from the CM
    //$jsonStr = getURL($url);
    $obj = $httpsig->get($url); // Now it returns an array object


//    //Decode the JSON //Now it comes decoded
//    $obj = json_decode($jsonStr, TRUE);
    if($obj === NULL){
        SimpleSAML\Logger::warning("JSON validation error #".json_last_error().": please, validate the content retrieved from $url: ".$obj);
        //throw new SimpleSAML_Error_Exception("Error validating JSON retrieved from $url");
    }
    
    return $obj;
}






//Write the microservice registry on its expected location after
//passing some strucutre tests and backing up previous value
function buildMsRegistry ($config, $url,$filepath)
{

    //Grab the microservice Registry from the CM
    $msRegistry = fetchMetadataDataset($config, $url);
    //SimpleSAML\Logger::debug('Microservice Registry:'.print_r($msRegistry,true));


    //Perform the checks
    if (!isset($msRegistry[0]))
        throw new SimpleSAML_Error_Exception("Empty ms registry");
    foreach ($msRegistry as $ms) {
        if (!isset($ms['msId']))
            throw new SimpleSAML_Error_Exception("Malformed ms entry: no msId");
        if (!isset($ms['rsaPublicKeyBinary']))
            throw new SimpleSAML_Error_Exception("Malformed ms entry: no rsaPublicKeyBinary");
        if (!isset($ms['publishedAPI']))
            throw new SimpleSAML_Error_Exception("Malformed ms entry: no publishedAPI");
    }

    //Dump the expected PHP object
    $output = "<?php\n\n\$esmoMicroservices = " . var_export($msRegistry, TRUE) . ';';


    //Backup the former file
    if (!copy($filepath, $filepath . '.bak')) {
        //throw new SimpleSAML_Error_Exception("Error backing up $filepath to $filepath" . ".bak. Check path exists and user permissions");
        SimpleSAML\Logger::warning("Error backing up $filepath to $filepath" . ".bak. Check path exists and user permissions");
    }
    //Write the dumped object to the proper metadata set file (overwriting previous version)
    if (!file_put_contents($filepath, $output)) {
        //throw new SimpleSAML_Error_Exception('Error: could not write MsRegistry object to file: ' . $filepath);
        SimpleSAML\Logger::warning('Error: could not write MsRegistry object to file: ' . $filepath);
    }
}





// ---------------- Main ----------------




//Load the CMHandler configuration
$cmHandlerMetadata = getCmMetadataFile($metadataDirectory,$cmFile);
SimpleSAML\Logger::debug('CMHandler config:'.print_r($cmHandlerMetadata,true));



//Write the microservice registry on its expected location
$set = $cmHandlerMetadata->getString('msRegistry',"");
if($set == "")
    throw new SimpleSAML_Error_Exception("msRegistry config field on CMHandler config empty or unset");

buildMsRegistry($cmHandlerMetadata, $cmHandlerMetadata->getString('msRegistryUrl'), $metadataDirectory.'/'.$set.'.php');


//Get the metadata of a random CM (so we can minimise the load of the bootstrapping URL CM)
try{
    $cmMetadata = sspmod_esmo_Tools::getMsMetadataByClass("CM",$cmHandlerMetadata->getString("msRegistry"));
    SimpleSAML\Logger::debug('ESMO randomly chosen Config Manager metadata: '.print_r($cmMetadata,true));
} catch (Exception $e) {
    throw new SimpleSAML_Error_Exception($e->getMessage());
}



//Get the list of local collections to write, and where to get the data from on the CM sets
$managedSets = $cmHandlerMetadata->getArray('managedSets',NULL);
if($managedSets == NULL)
    throw new SimpleSAML_Error_Exception("managedSets config field on CMHandler config empty or unset");



//The url of the external entity API at the CM
$baseurl = getApiURL($cmMetadata,'CM','externalEntities');


//For each managed set, fetch and filter the source collections
foreach($managedSets as $setName => $setData){
    SimpleSAML\Logger::debug('Building set: '.$setName);
    
    $collections = $setData['collections'];
    $variable    = $setData['variable'];
    
    $protocols = NULL;
    if(isset($setData['protocols']))
        $protocols   = $setData['protocols'];
    
    //Get the collections
    $entities = array();
    $allErrors = True;
    foreach($collections as $collection){
        $thisSet = NULL;

        try{
            //Grab the collection from the CM
            $thisSet = fetchMetadataDataset($cmHandlerMetadata, $baseurl."/".$collection);
            SimpleSAML\Logger::debug('Retrieved collection -'.$baseurl.'/'.$collection.'-:'.print_r($thisSet,true));
            $allErrors = False;
        }catch (Exception $err) {
            SimpleSAML\Logger::warning('Error retrieving external entity set: '.$err->getMessage());
        }

        //Accumulate
        if($thisSet != NULL)
            $entities = array_merge($entities,$thisSet);
    }

    // If no files could be retrieved, warn and leave former file as is
    if($allErrors){
        SimpleSAML\Logger::warning('Error retrieving all external sets for this file. Keeping former one.');
        continue;
    }

    
    //Filter by protocol, if specified
    if($protocols != NULL){

        //If the protocol of the entity is in the list, keep it
        // If protocol not set, keep it as well
        $filteredSet = array();
        foreach($entities as $entity)
            if( ! isset($entity['protocol'])
            || $entity['protocol'] == NULL
            || in_array($entity['protocol'],$protocols) )
                $filteredSet []= $entity;
        
        $entities = $filteredSet;
    } 
    
    //Dump the expected PHP objects
    $output = "<?php\n\n";
    foreach($entities as $entity){
        
        //Transform the entity from ESMO format to SSP format
        $sspEntity = sspmod_esmo_Esmo::buildSspEntity($entity);
        
        //Serialize it
        $output .= '$'.$variable."['".$entity['entityId']."'] = ".var_export($sspEntity,TRUE).";\n\n";
    }
    
    
    $filepath = $metadataDirectory.'/'.$setName.'.php';
    
    
    //Backup the former file
    if(!copy($filepath, $filepath.'.bak')) {
        //throw new SimpleSAML_Error_Exception("Error backing up $filepath to $filepath" . ".bak. Check that path exists and user has permissions");
        SimpleSAML\Logger::warning("Error backing up $filepath to $filepath" . ".bak. Check that path exists and user has permissions");
    }

    //Write the dumped object to the proper metadata set file (overwriting previous version)
    if(!file_put_contents($filepath,$output)) {
        //throw new SimpleSAML_Error_Exception('Error: could not write '.$setName.' object to file: '.$filepath);
        SimpleSAML\Logger::warning('Error: could not write '.$setName.' object to file: '.$filepath);
    }
}
