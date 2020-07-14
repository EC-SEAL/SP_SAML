<?php
/**
 * ESMO Config Manager metadata, to boostrap the download of the microservice registry.
 *
 * All possible options included in this example in comments
 */


$esmoConfigManagerConf = array (
    
    
    //[Mandatory] Config Manager URL where the msRegistry can be fetched  (to be used by the CMHandler)
    'msRegistryUrl' =>  'http://cm.ms:8080/cm/metadata/microservices',
    
    
    //[Mandatory] Metadata set name where the msRegistry are be found
    'msRegistry' =>  'esmo-microservices',
    

    //[Optional] List of metadata sets to be retrieved from the CM and
    //put in a local collection.
    'managedSets' => array(

        //ESMO IDPs
        'clave-idp-remote' => array(
            
            //Which CM collections should be put here (all collections
            //will be merged, in case of collission, last collection
            //will prevail)
            'collections' => array('IdP'),
            
            //Collections can be filtered by protocol (only matching
            //values will be added), if not set, all protocols will be accepted
        //  'protocols'   => array('SAML2-eIDAS'),

            //The name of the global variable expected in this set
            'variable'    => 'claveMeta',
        ),
        
        //ESMO APs
        'saml20-idp-remote' => array(
            'collections' => array('IdP'),
            // 'protocols'   => array('SAML2'),
            'variable'    => 'metadata',
        ),

        //ESMO SPs with SAML2-eIDAS support
        'clave-sp-remote' => array(
            'collections' => array('SP'),
            //  'protocols'   => array('SAML2-eIDAS'),
            'variable'    => 'claveMeta',
        ),

        //ESMO SPs with standard SAML2 support
        'saml20-sp-remote' => array(
            'collections' => array('SP'),
            //  'protocols'   => array('SAML2'),
            'variable'    => 'metadata',
        ),
    ),
    
);