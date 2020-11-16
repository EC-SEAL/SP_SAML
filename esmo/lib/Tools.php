<?php


class sspmod_esmo_Tools {
    
    //TODO: SimpleSAMLConfiguration soporta devolver arrays de arrays?
    //TODO: we get it from a fixed file in metadata. How to build that file is a matter of the CmHandler cron job (use my json2php script as the base to build the CMHandler, make it read config (the CM url and others if needed) from the authsource)
    
    
    //Loads the ESMO microservice metadata file 
    // @param $set which metadada set to read (the name of the file on the metadata dir without extension)
    // @return an array with the list of microservices metadata objects
    public static function getMsMetadataFile($set){
        
        assert('is_string($set)');
        
        $globalConfig = SimpleSAML_Configuration::getInstance();
        $metadataDirectory = $globalConfig->getString('metadatadir', 'metadata/');
        $metadataDirectory = $globalConfig->resolvePath($metadataDirectory) . '/';
        
        $metadataFile = $metadataDirectory.'/'.$set.'.php';
        try{
            //Don't use _once or the global variable might get unset.
            require($metadataFile);
        }catch(Exception $e){
            throw new SimpleSAML_Error_Exception("ESMO Microservice file ".$metadataFile." not found.");
        }
        
        if(!isset($esmoMicroservices))
            throw new SimpleSAML_Error_Exception("Microservice set ".$set.": malformed or undefined global microservices variable");
        
        return $esmoMicroservices;
    }


    

    //Loads a metadata set for an ESMO microservice 
    // @param $msId The ID of the microservice 
    // @param $set which metadada set to read from (the name of the file on the metadata dir without extension)
    // @return a SimpleSAML_Configuration object with the metadata for the entity
    public static function getMsMetadata($msId, $set){
        
        assert('is_string($msId)');
        
        $msArray = self::getMsMetadataFile($set);
        
        foreach ($msArray as $ms)
            if( $ms['msId'] === $msId){
                //To comply with the expected format, we add the msId as entityid
                if(!isset($ms['entityid']))
                    $ms['entityid'] = $ms['msId'];
                
                return SimpleSAML_Configuration::loadFromArray($ms);
            }
        
        throw new Exception("Entity ".$msId." not found in set ".$set); 
    }
    

    //Loads a metadata set for a random ESMO microservice implementing a specific apiClass
    // @param $apiClass The Id of the interface we want the selected microservice to implement
    // @param $set which metadada set to read from (the name of the file on the metadata dir without extension)
    // @return a SimpleSAML_Configuration object with the metadata for the entity
    public static function getMsMetadataByClass($apiClass, $set){
        
        assert('is_string($apiClass)');
        
        $msArray = self::getMsMetadataFile($set);
        
        $found = array();
        foreach ($msArray as $ms){
            foreach ($ms['publishedAPI'] as $api){
                
                if( $api['apiClass'] === $apiClass){
                    $found []= $ms;
                    break;
                }
                
            }
        }

        SimpleSAML_Logger::debug('MICROSERVICES OF CLASS '.$apiClass.':'.print_r($found,true));
        
        if (count($found) <=0)
            throw new SimpleSAML_Error_Exception("No entity of class ".$apiClass." found in set ".$set); 
        
        
        //Choose a random one
        $chosen = rand(0,count($found)-1);
                
        //To comply with the expected format, we add the msId as entityid
        if(!isset($found[$chosen]['entityid'])
        && isset($found[$chosen]['msId']))
            $found[$chosen]['entityid'] = $found[$chosen]['msId'];

        SimpleSAML_Logger::debug('Chosen microservice('.$chosen.'):'.print_r($found[$chosen],true));
        
        return SimpleSAML_Configuration::loadFromArray($found[$chosen]);
    }


    public static function getBasePath($url)
    {
        $urlObj = parse_url($url);
        $urlPath = $urlObj['path'];
        $urlDict = explode('/', $urlPath, 3);
        if($urlDict == NULL || !isset($urlDict[1]))
            return "";
        $basePath = $urlDict[1];
        if($basePath == NULL)
            return "";
        return $basePath;
    }
}