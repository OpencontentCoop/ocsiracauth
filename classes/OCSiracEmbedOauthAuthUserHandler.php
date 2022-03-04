<?php

class OCSiracEmbedOauthAuthUserHandler extends OCSiracAuthUserHandler implements OCSiracReloadableHandlerInterface
{
    public function reload(array $userData = array())
    {
        foreach ($userData as $key => $value){
            $_SERVER[$key] = $value;
        }
        $this->initialize();
        $this->mappedVars['UserLogin'] = str_replace('TINIT-', '', $this->mappedVars['UserLogin']);
        $this->mappedVars['FiscalCode'] = str_replace('TINIT-', '', $this->mappedVars['FiscalCode']);
        $this->mappedVars['Attributes']['fiscal_code'] = str_replace('TINIT-', '', $this->mappedVars['Attributes']['fiscal_code']);
    }

    public function logout(eZModule $module)
    {
        if (eZHTTPTool::instance()->hasSessionVariable('SIRACUserLoggedIn')) {
            eZHTTPTool::instance()->removeSessionVariable('SIRACUserLoggedIn');
            OCSiracEmbedOauth::instance()->logout();
        }
        $module->redirectTo('/');
    }

}
