<?php
/**
 * The ESMO microservice protocol implementation of a SSP IDP/AP
 * interface
 *
 * @author Francisco José Aragó Monzonís, UJI <farago@uji.es>
 * @package esmo
 */

// TODO: implement


class sspmod_esmo_IdP
{
    
    /**
     * The identifier for this IdP.
     *
     * @var string
     */
    private $id;
    
    
    /**
     * The configuration for this IdP.
     *
     * @var SimpleSAML_Configuration
     */
    private $config;


    /**
     * Our authsource.
     *
     * @var \SimpleSAML\Auth\Simple
     */
    private $authSource;
    
    
    /**
     * Our authsource ID.
     *
     * @var string
     */
    private $authSourceId;
    
    /**
     * Retrieve the ID of this IdP.
     *
     * @return string The ID of this IdP.
     */
    public function getId()
    {
        return $this->id;
    }
    
    
    
    /**
     * Retrieve the configuration for this IdP.
     *
     * @return SimpleSAML_Configuration The configuration object.
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    
    /**
     * Retrieve an IdP by ID.
     *
     * @param string $id The identifier of the IdP.
     *
     * @return SimpleSAML_IdP The IdP.
     */
    public static function getById($id,$protocol)
    {
        assert('is_string($id)');
        assert('is_string($protocol)');
        
        $idp = new self($id,$protocol);
        
        return $idp;
    }


    /**
     * Retrieve the IdP "owning" the state.
     *
     * @param array &$state The state array.
     *
     * @return SimpleSAML_IdP The IdP.
     */
    public static function getByState(array &$state)
    {
        assert('isset($state["core:IdP"])');
        assert('isset($state["esmo:IdP:protocol"])');
        
        return self::getById($state['core:IdP'], $state['esmo:IdP:protocol']);
    }




    /**
     * Is the current user authenticated?
     *
     * @return boolean True if the user is authenticated, false otherwise.
     */
    public function isAuthenticated()
    {
        return $this->authSource->isAuthenticated();
    }
    
    
    
    
    
    
    /**
     * Initialize an IdP.
     *
     * @param string $id The identifier of this IdP.
     * @param string $protocol The identifier of the protocol to use, that will determine the authsource to call.
     *
     * @throws SimpleSAML_Error_Exception If the IdP is disabled or no such auth source was found.
     */
    private function __construct($id, $protocol)
    {
        assert('is_string($id)');
        
        SimpleSAML_Logger::debug('Called ESMO IDP construct.');
        
        
        $this->id = $id;
        
        //Get the Hosted IdP config
        $this->config = sspmod_clave_Tools::getMetadataSet($id,"esmo-idp-hosted");
        SimpleSAML_Logger::debug('ESMO IDP hosted metadata: '.print_r($this->config,true));
        
        
        //Get the associated AuthSource (as defined in config, and wrapped in the Simple auth class)
        
        //Default value (if no protocol specific authsource is determined)
        $auth = $this->config->getString('auth');
        
        
        
        $authSources = $this->config->getArray('authSources',NULL);
        if($authSources !== NULL
        && $protocol !== NULL){
            if(isset($authSources[$protocol])){
                $auth = $authSources[$protocol];
            }
        }
        
        
        if (SimpleSAML_Auth_Source::getById($auth) !== null) {
            $this->authSource = new SimpleSAML_Auth_Simple($auth);
        } else {
            throw new SimpleSAML_Error_Exception('No such "'.$auth.'" auth source found.');
        }

        $this->authSourceId = $auth;
        
        SimpleSAML_Logger::debug('ESMO IDP selected authSource: '.$auth);
    }
    
    
    
    
    /**
     * Process authentication requests. Same implementation as
     * base. but need to override as the callback has the base class
     * hardcoded instead of getting the actual instance classname
     *
     * @param array &$state The authentication request state.
     */
    public function handleAuthenticationRequest(array &$state)
    {
        assert('isset($state["Responder"])');

        $state['core:IdP'] = $this->id;

        if (isset($state['SPMetadata']['entityid'])) {
            $spEntityId = $state['SPMetadata']['entityid'];
        } elseif (isset($state['SPMetadata']['entityID'])) {
            $spEntityId = $state['SPMetadata']['entityID'];
        } else {
            $spEntityId = null;
        }
        $state['core:SP'] = $spEntityId;
        
        
        $state['IdPMetadata'] = $this->getConfig()->toArray();
        $state['ReturnCallback'] = array('sspmod_esmo_IdP', 'postAuth');
        
        try {
            $this->authenticate($state);
            assert('FALSE');
        } catch (SimpleSAML_Error_Exception $e) {
            SimpleSAML_Auth_State::throwException($state, $e);
        } catch (Exception $e) {
            $e = new SimpleSAML_Error_UnserializableException($e);
            SimpleSAML_Auth_State::throwException($state, $e);
        }
    }



    /**
     * Called after authproc has run.
     *
     * @param array $state The authentication request state array.
     */
    public static function postAuthProc(array $state)
    {
        assert('is_callable($state["Responder"])');

        if (isset($state['core:SP'])) {
            $session = SimpleSAML_Session::getSessionFromRequest();
            $session->setData(
                'core:idp-ssotime',
                $state['core:IdP'].';'.$state['core:SP'],
                time(),
                SimpleSAML_Session::DATA_TIMEOUT_SESSION_END
            );
        }

        call_user_func($state['Responder'], $state);
        assert('FALSE');
    }


    /**
     * The user is authenticated.
     *
     * @param array $state The authentication request state array.
     *
     * @throws SimpleSAML_Error_Exception If we are not authenticated.
     */
    public static function postAuth(array $state)
    {
        $idp = sspmod_esmo_IdP::getByState($state);

        if (!$idp->isAuthenticated()) {
            throw new SimpleSAML_Error_Exception('Not authenticated.');
        }

        $state['Attributes'] = $idp->authSource->getAttributes();

        if (isset($state['SPMetadata'])) {
            $spMetadata = $state['SPMetadata'];
        } else {
            $spMetadata = array();
        }

        if (isset($state['core:SP'])) {
            $session = SimpleSAML_Session::getSessionFromRequest();
            $previousSSOTime = $session->getData('core:idp-ssotime', $state['core:IdP'].';'.$state['core:SP']);
            if ($previousSSOTime !== null) {
                $state['PreviousSSOTimestamp'] = $previousSSOTime;
            }
        }

        $idpMetadata = $idp->getConfig()->toArray();

        $pc = new SimpleSAML_Auth_ProcessingChain($idpMetadata, $spMetadata, 'idp');

        $state['ReturnCall'] = array('sspmod_esmo_IdP', 'postAuthProc');
        $state['Destination'] = $spMetadata;
        $state['Source'] = $idpMetadata;

        $pc->processState($state);

        self::postAuthProc($state);
    }
    



    /**
     * Authenticate the user.
     *
     * This function authenticates the user.
     *
     * @param array &$state The authentication request state.
     *
     * @throws SimpleSAML_Error_NoPassive If we were asked to do passive authentication.
     */
    private function authenticate(array &$state)
    {
        
        $this->authSource->login($state);
    }
    
    
    
    
    
    /**
     * Find the logout handler of this IdP.
     *
     * @return \SimpleSAML\IdP\LogoutHandlerInterface The logout handler class.
     *
     * @throws SimpleSAML_Error_Exception If we cannot find a logout handler.
     */
    public function getLogoutHandler()
    {
        throw new SimpleSAML_Error_Exception('Logout not supported in ESMO.');
    }
    
    
    /**
     * Finish the logout operation.
     *
     * This function will never return.
     *
     * @param array &$state The logout request state.
     */
    public function finishLogout(array &$state)
    {
        throw new SimpleSAML_Error_Exception('Logout not supported in ESMO.');
    }


    /**
     * Process a logout request.
     *
     * This function will never return.
     *
     * @param array       &$state The logout request state.
     * @param string|null $assocId The association we received the logout request from, or null if there was no
     * association.
     */
    public function handleLogoutRequest(array &$state, $assocId)
    {
        throw new SimpleSAML_Error_Exception('Logout not supported in ESMO.');
    }


    /**
     * Process a logout response.
     *
     * This function will never return.
     *
     * @param string                          $assocId The association that is terminated.
     * @param string|null                     $relayState The RelayState from the start of the logout.
     * @param SimpleSAML_Error_Exception|null $error The error that occurred during session termination (if any).
     */
    public function handleLogoutResponse($assocId, $relayState, SimpleSAML_Error_Exception $error = null)
    {
        throw new SimpleSAML_Error_Exception('Logout not supported in ESMO.');
    }


    /**
     * Log out, then redirect to a URL.
     *
     * This function never returns.
     *
     * @param string $url The URL the user should be returned to after logout.
     */
    public function doLogoutRedirect($url)
    {
        throw new SimpleSAML_Error_Exception('Logout not supported in ESMO.');
    }


    /**
     * Redirect to a URL after logout.
     *
     * This function never returns.
     *
     * @param sspmod_esmo_IdP $idp Deprecated. Will be removed.
     * @param array          &$state The logout state from doLogoutRedirect().
     */
    public static function finishLogoutRedirect(sspmod_esmo_IdP $idp, array $state)
    {
        throw new SimpleSAML_Error_Exception('Logout not supported in ESMO.');
    }
    
    
}

